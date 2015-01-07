<?php

namespace tracking;

require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/global.inc.php');

require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/tracking/Funding.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/tracking/Commitments.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/tracking/Investigator.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/tracking/ExternalInvestigator.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/tracking/COI.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/tracking/Compliance.php');

global $configInfo;

/* Form Status */
define('PRESUBMITTED', 0);
define('SUBMITTED', 1);
define('COMPLETE', 2);
define('FILEPATH', $configInfo['tracking_docs']);
define('DEAN_NOTIFICATION_THRESHOLD_DAYS', '+5 days'); // threshold for immediate notification to Dean

/**
 * A tracking form
 */
class TrackingForm
{
    /**
     * The constructor
     */
    function __construct()
    {   global $niceday;

        $this->createdDate = date($niceday);
        $this->modifiedDate = date("$niceday G:i");
        $this->deadline = date($niceday);
    }

    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }

        return +$this;
    }

    public function addApproval($requiredApproval) {
        $this->approvals[] = $requiredApproval;
    }

    /**
     * Whether this project has funding associated
     */
    public function hasFunding() {
            return $this->funding->hasFunding();
    }

    /**
     * Determine if the person who submitted the form is also the principal investigator
     *
     * @return bool - true if user who submitted form is also the PI
     */
    public function isSubmitterPI() {
        return $this->userIsPI;
    }

    /**
     * Retrieve a tracking form from the database and populate the form object
     *
     * @param $trackingId - the trackingId of the form to retrieve
     * @return - form data, null if no trackingId is supplied
     */
    public function retrieveForm($trackingId) {
        global $db;

        if(!isset($trackingId)) {
            return null;
        }

        $this->trackingFormId = $trackingId;

        $sql = sprintf("SELECT * FROM forms_tracking
                        WHERE form_tracking_id = %s", $trackingId);
        $formData = $db->getRow($sql);

        $this->loadApprovals(); // load the approvals from the approval table

        $filePath = FILEPATH . $this->userId . '/' . $this->trackingFormId;
        $this->loadFilesFromDir($filePath); // determine what files are associated with this trackingform

        $this->populateForm($formData);

        return $formData;
    }

    /**
     * Save any changes to the form
     */
    public function saveMe()
    {
        global $db;
        $sql = sprintf(
            "UPDATE `forms_tracking` SET
                      `tracking_name`  = '%s',
                      `synopsis` = '%s',
                      `deadline` = '%s',
                      `modified` = NOW(),
                      `pi` = '%s',
                      `pi_id` = %s,
                      `costudents` = %s,
                      `coresearchers` = '%s',
                      `funding` = %s,
                      `status` = %s
                  WHERE `form_tracking_id` = %s",
            mysql_real_escape_string($this->projectTitle),
            mysql_real_escape_string($this->synopsis),
            $this->deadline,
            $this->userIsPI,
            $this->principalInvestigatorId,
            $this->coResearcherStudents,
            mysql_real_escape_string($this->coResearchersExternal),
            $this->hasFunding() ? 1 : 0,
            $this->status,
            $this->trackingFormId
        );

        $result=$db->Execute($sql);

        if(!$result) {
            throw new Exception('Tracking form cannot be saved.');
        }

        if($this->isSubmitterPI() == true) {
            $this->clearPrincipalInvestigators();  // remove existing PI from coresearchers table if one exists
        }

        if(isset($this->externalPI)) {
            $this->clearPrincipalInvestigators();  // remove existing PI from coresearchers table if one exists
            $this->externalPI->save();
        }

        $this->funding->save();

        $this->commitments->save();

        foreach($this->coi as $coi) {
            $coi->save();
        }

        $this->compliance->save();

        $this->calculateRequiredApprovals();
    }

    /**
     * Submit the tracking form
      */
    public function submit() {
        global $db;

        $this->status = SUBMITTED;
        $this->saveMe();

        $sql = "SELECT d.division_id FROM users as u
                LEFT JOIN departments AS d ON d.department_id = u.department_id
                WHERE u.user_id = " . $this->userId;
        $divisionId = $db->getRow($sql);

        // save the approvals to the database
        foreach($this->approvals as $approval) {
            $approval->save($divisionId['division_id']);
        }

        $sql = sprintf("UPDATE `forms_tracking` SET
                                  `submit_date` = NOW()
                              WHERE `form_tracking_id` = %s",
                       $this->trackingFormId
                       );
        $db->Execute($sql);

        $this->sendSubmissionConfirmationEmail();

        // send notifications
        $this->notifyORS();

        // notify Dean if deadline is within DEAN_NOTIFICATION_THRESHOLD_DAYS
        if(isset($this->deadline)) {
            $dateThreshold = strtotime(DEAN_NOTIFICATION_THRESHOLD_DAYS);
            $deadline = strtotime($this->deadline);
            if($deadline < $dateThreshold) {
                $this->notifyDean();
            }
        }

        if($this->compliance->requiresBehavioural()) {
            $this->notifyHREB();
        }
    }

    /**
     * Populate the object
     *
     * @param $formData - the form data from the database
     */
    protected function populateForm($formData) {
        global $niceday;

        $this->trackingFormId           = $formData['form_tracking_id'];
        $this->projectTitle             = $formData['tracking_name'];
        $this->synopsis                 = $formData['synopsis'];
        $this->deadline                 = $formData['deadline'];
        $this->createdDate              = date($niceday, strtotime($formData['created']));
        $this->modifiedDate             = date("$niceday G:i", strtotime($formData['modified']));
        $this->status                   = $formData['status'];
        $this->userId                   = $formData['user_id'];
        $this->userIsPI                 = $formData['pi'];
        $this->principalInvestigatorId  = $formData['pi_id'];
        $this->coResearcherStudents     = $formData['costudents'];
        $this->coResearchersExternal    = $formData['coresearchers'];


        $this->submitter = new Investigator($formData['user_id']);
        $this->loadExternalPI();
        $this->loadCoresearchers();
        $this->funding = new Funding($formData);
        $this->commitments = new Commitments($formData);
        $this->coi = $this->loadCOI();
        $this->compliance = new Compliance($formData);
    }


    /**
     * Get the HREB status of this tracking form
     *
     * @return string - the HREB status
     */
    public function HrebStatus() {
        return $this->compliance->getHrebStatus();
    }

    /**
     * Get the HREB status of this tracking form
     *
     * @return string - the HREB status
     */
    public function HrebStatusCode() {
        return $this->compliance->getHrebStatusCode();
    }

    /**
     * Check the status of Dean approvals for this form
     *
     * Approved if Commitments (or Dean Review) approved.
     */
    public function isDeanApproved() {
        global $db;

        $sql = sprintf('SELECT approvalType.type, approvalType.friendlyName, approvals.*
                                             FROM forms_tracking_approvals as approvals
                                             LEFT JOIN forms_approval_type as approvalType ON approvals.approval_type_id = approvalType.id
                                             WHERE `approvals`.`tracking_id` = %s AND `approval_type_id` IN (%s, %s)'
            , $this->trackingFormId, COMMITMENTS, DEAN_REVIEW);
        $approvals = $db->getAll($sql);

        if(empty($approvals)) {
            return true;  // no required approvals we assume approved.
        } else {
            $numApprovals = count($approvals);
            $i = 0;
            $approved = 1;
            // iterate through each approval and bitwise 'and' with the previous result
            while($i < $numApprovals) {
                $approved = $approvals[$i]['approved'] & $approved;
                $i++;
            }
        }

        return $approved;
    }

    /**
     * Check the status of ORS approvals for this form
     *
     * Approved if COI (or ORS Review) approved.
     */
    public function isOrsApproved() {
        global $db;

        $sql = sprintf('SELECT approvalType.type, approvalType.friendlyName, approvals.*
                                             FROM forms_tracking_approvals as approvals
                                             LEFT JOIN forms_approval_type as approvalType ON approvals.approval_type_id = approvalType.id
                                             WHERE `approvals`.`tracking_id` = %s AND `approval_type_id` IN (%s, %s)'
            , $this->trackingFormId, COI, ORS_REVIEW);
        $approvals = $db->getAll($sql);

        if(empty($approvals)) {
            return true;  // no required approvals we assume approved.
        } else {
            $numApprovals = count($approvals);
            $i = 0;
            $approved = 1;
            // iterate through each approval and bitwise 'and' with the previous result
            while($i < $numApprovals) {
                $approved = $approvals[$i]['approved'] & $approved;
                $i++;
            }
        }

        return $approved;
    }

    /**
     * Clears all the principal investigators from the CoResearchers table
     */
    public function clearPrincipalInvestigators() {
        global $db;

        $sql = sprintf("DELETE FROM `forms_tracking_coresearchers`
                        WHERE `form_tracking_id` = %s AND isPi = 1", $this->trackingFormId);

        $result = $db->Execute($sql);

        if(!$result) {
            echo ("Unable to clear principal invesitgators from database");
        }
    }

    /**
     * Whether the provided userId is the principal investigator
     *
     * @param $userId - the user id
     * @return bool - true of the provided userid is the principal investigator
     */
    public function userIsPI($userId) {
        if($this->principalInvestigatorId == $userId) {
            return true;
        }
        return false;
    }

    /**
     * Get the details of the PI for display purposes
     */
    public function getPIDisplayDetails() {
        $userDetails = array();

        if($this->isSubmitterPI()) {
            $userDetails['firstName'] = $this->submitter->firstName;
            $userDetails['lastName'] = $this->submitter->lastName;
            $userDetails['departmentName'] = $this->submitter->departmentName;
        } elseif($this->hasMruPI()) {
            foreach($this->coResearchers as $coResearcher) {
                if($coResearcher->isPI == true) {
                    $userDetails['firstName'] = $coResearcher->firstName;
                    $userDetails['lastName'] = $coResearcher->lastName;
                    $userDetails['departmentName'] = $coResearcher->departmentName;
                }
            }
        } elseif($this->hasExternalPI()) {
            $userDetails['firstName'] = $this->externalPI->firstName;
            $userDetails['lastName'] = $this->externalPI->lastName;
            $userDetails['departmentName'] = 'External';
            if($this->externalPI->institution != '') {
                $userDetails['departmentName'] .= ' - ' . $this->externalPI->institution;
            }
        }

        return $userDetails;
    }


    /**
     * Whether this tracking form has an external PI \
     */
    public function hasExternalPI() {
         if(!$this->userIsPI && $this->principalInvestigatorId == 0) {
             return true;
         }
        return false;
    }

    /*
     * Whether this tracking form has an MRU PI
     */
    public function hasMruPI() {
        if(!$this->userIsPI && $this->principalInvestigatorId > 0) {
            return true;
        }
        return false;
    }

    /**
     * Load any external PI's
     */
    protected function loadExternalPI() {
        global $db;


        $sql = sprintf("SELECT * FROM `forms_tracking_external_pi`
                        WHERE `tracking_id`= %s", $this->trackingFormId);
        $externalPI = $db->getRow($sql);



        $userDetails = array(
            'firstName' => $externalPI['firstName'],
            'lastName' => $externalPI['lastName'],
            'phone' => $externalPI['phone'],
            'email' => $externalPI['email'],
            'address1' => $externalPI['address1'],
            'address2' => $externalPI['address2'],
            'address3' => $externalPI['address3'],
            'institution' => $externalPI['institution']
        );
        $this->addExternalPI($userDetails);
    }

    /**
     * Load the co-researchers associated with this project
     */
    protected function loadCoresearchers() {
        global $db;

        $this->coResearchers = array();  // first clear any coresearchers that are already loaded to avoid duplicates

        $sql = sprintf("SELECT user_id, isPi FROM `forms_tracking_coresearchers`
                        WHERE `form_tracking_id`= %s", $this->trackingFormId);
        $coResearchers = $db->getAll($sql);

        if($coResearchers) {
            foreach($coResearchers as $coResearcher){
                $this->coResearchers[] = new Investigator($coResearcher['user_id'], $coResearcher['isPi'] == '1' ? true : false);
            }
        }
    }

    /**
     * Load the co-researchers associated with this project
     */
    protected function loadCOI() {
        global $db;

        $sql = sprintf("SELECT form_coi_id FROM `forms_coi`
                        WHERE `form_tracking_id`= '%s'
                        ", $this->trackingFormId);
        $result = $db->getAll($sql);

        $coi = array();
        foreach($result as $coiId){
            $coi[] = new COI($coiId['form_coi_id']);
        }

        return $coi;
    }

    /**
     * Load the approvals from the approval database and populate the tracking form object with them
     */
    private function loadApprovals() {
        global $db;

        $sql = sprintf("SELECT * FROM `forms_tracking_approvals`
                        WHERE `tracking_id` = %s", $this->trackingFormId);
        $approvals = $db->getAll($sql);


        if(is_array($approvals)) {
            foreach($approvals as $approval) {
                switch($approval['approval_type_id']) {
                    case ETHICS_BEHAVIOURAL:
                        require_once('classes/tracking/approvals/EthicsBehavioural.php');
                        $hreb = new \tracking\approval\EthicsBehavioural($this->trackingFormId);
                        $hreb->status = $approval['approved'] == 1 ? APPROVED : PRESUBMITTED;
                        $hreb->comments = $approval['comments'];
                        $hreb->dateApproved = $approval['date_approved'];
                        $hreb->dateSubmitted = $approval['date_submitted'];
                        $this->addApproval($hreb);
                        break;
                    case COMMITMENTS:
                        require_once('classes/tracking/approvals/Commitments.php');
                        $commitments = new \tracking\approval\Commitments($this->trackingFormId);
                        $commitments->status = $approval['approved'] == 1 ? APPROVED : PRESUBMITTED;
                        $commitments->comments = $approval['comments'];
                        $commitments->dateApproved = $approval['date_approved'];
                        $commitments->dateSubmitted = $approval['date_submitted'];
                        $this->addApproval($commitments);
                        break;
                    case COI:
                        require_once('classes/tracking/approvals/COI.php');
                        $COI = new \tracking\approval\COI($this->trackingFormId);
                        $COI->status = $approval['approved'] == 1 ? APPROVED : PRESUBMITTED;
                        $COI->comments = $approval['comments'];
                        $COI->dateApproved = $approval['date_approved'];
                        $COI->dateSubmitted = $approval['date_submitted'];
                        $this->addApproval($COI);
                        break;
                    case DEAN_REVIEW:
                        require_once('classes/tracking/approvals/DeanReview.php');
                        $deanReview = new \tracking\approval\DeanReview($this->trackingFormId);
                        $deanReview->status = $approval['approved'] == 1 ? APPROVED : PRESUBMITTED;
                        $deanReview->comments = $approval['comments'];
                        $deanReview->dateApproved = $approval['date_approved'];
                        $deanReview->dateSubmitted = $approval['date_submitted'];
                        $this->addApproval($deanReview);
                        break;
                    case ORS_REVIEW:
                        require_once('classes/tracking/approvals/ORSReview.php');
                        $orsReview = new \tracking\approval\ORSReview($this->trackingFormId);
                        $orsReview->status = $approval['approved'] == 1 ? APPROVED : PRESUBMITTED;
                        $orsReview->comments = $approval['comments'];
                        $orsReview->dateApproved = $approval['date_approved'];
                        $orsReview->dateSubmitted = $approval['date_submitted'];
                        $this->addApproval($orsReview);
                        break;
                    default:
                        break;
                }
            }
        }
    }

    /**
     * Determine what approvals are required for this form
     *
     * Currently we are only using Commitments and COI, but in the future we may also include ethics
     */
    private function calculateRequiredApprovals() {

        if($this->commitments->requiresApproval() == true) {
            require_once('classes/tracking/approvals/Commitments.php');
            $this->addApproval(new \tracking\approval\Commitments($this->trackingFormId));
        }

        // If the form doesn't have commitments, then we still want the Dean to review it
        //  so we add this approval.
        if(!$this->commitments->requiresApproval()) {
            require_once('classes/tracking/approvals/DeanReview.php');
            $this->addApproval(new \tracking\approval\DeanReview($this->trackingFormId));
        }

        if($this->COIRequiresApproval() == true) {
            require_once('classes/tracking/approvals/COI.php');
            $this->addApproval(new \tracking\approval\COI($this->trackingFormId));
        } else {
            // there are no COI, but ORS still needs to review so apply ORSReview
            require_once('classes/tracking/approvals/ORSReview.php');
            $this->addApproval(new \tracking\approval\ORSReview($this->trackingFormId));
        }

        if($this->compliance->requiresBehavioural() == true) {
            require_once('classes/tracking/approvals/EthicsBehavioural.php');
            $this->addApproval(new \tracking\approval\EthicsBehavioural($this->trackingFormId));
        }


/*        if($this->compliance->requiresHealth() == true) {
            require_once('classes/tracking/approvals/EthicsHealth.php');
            $this->addApproval(new \tracking\approval\EthicsHealth($this->trackingFormId));
        }

        if($this->compliance->requiresAnimal() == true) {
            require_once('classes/tracking/approvals/EthicsAnimal.php');
            $this->addApproval(new \tracking\approval\EthicsAnimal($this->trackingFormId));
        }

        require_once('classes/tracking/approvals/EthicsBiohazard.php');
        if($this->compliance->requiresBiohazard() == true) {
            $this->addApproval(new \tracking\approval\EthicsBiohazard($this->trackingFormId));
        }*/
    }

    /**
     * Get a conflict of interest form that belong to this form by ID
     *
     * @param $coiId - the id of the COI form
     * @return mixed - COI() - false if not found
     */
    public function getCoiById($coiId) {

        foreach($this->coi as $coi) {
            if($coi->coiId == $coiId) {
                return $coi;
            }
        }
        return false;
    }

    /**
     * Delete a COI form from the tracking form
     *
     * @param $coiId - the COI form to delete
     */
    public function deleteCOI($coiId) {
        $coi = $this->getCoiById($coiId);
        $coi->delete();

        foreach($this->coi as $key=>$coi) {
            if($coi->coiId == $coiId) {
                unset($this->coi[$key]);  //disassociate it from the internal array

            }
        }
    }

    /**
     * @param $fileDetails - array('trackingId' => tracking form id
     *                             'name' => filename,
                                   'extension' => file extension,
                                   'path' => path to file,
                                   'size' => file size on bytes,
                                   'description' => file description);
     * @return bool - false if file with same name and tracking ID already exists in table
     */
    public function addFile($fileDetails) {
        global $db;

        $sql = "SELECT COUNT(*) FROM `forms_tracking_files` WHERE `name` = '" . $fileDetails['name'] . "' AND `trackingId` = " . $fileDetails['trackingId'];
        $count = $db->GetRow($sql);

        if($count['COUNT(*)'] > 0) {
            return false;
        }

        $sql = vsprintf("INSERT INTO `forms_tracking_files` (`trackingId`, `name`, `extension`, `path`, `size`, `description`)
                VALUES (%s, '%s', '%s', '%s', '%s', '%s') ", $fileDetails);
        $db->Execute($sql);

        return true;
    }

    /**
     * Delete a file associated with the tracking form
     *
     * @param $filename - the name of the file to delete
     * @throws \Exception - on unable to delete file due to missing userId or trackingId
     */
    public function deleteFile($filename) {
        global $db;

        $userId= $this->userId;
        $trackingId = $this->trackingFormId;

        $basePath = FILEPATH . $userId. '/' . $trackingId;
        $filePath = $basePath . '/' . $filename;


        if(!isset($userId) || !isset($trackingId)) {
            throw new Exception("Unable to delete file " . $filePath . ".  Missing tracking form information.");
        }


        $basePath = FILEPATH . $userId. '/' . $trackingId;
        $filePath = $basePath . '/' . $filename;

        $filePath = realpath($filePath);
        if(is_readable($filePath)){
            unlink($filePath);
            $sql = "DELETE FROM `forms_tracking_files` WHERE `name` = '" . $filename . "' AND `trackingId` = " . $trackingId;
            $db->Execute($sql);
        }

        $this->loadFilesFromDir($basePath); // refresh what files are associated with this trackingform
    }

    /**
     * Create a new blank COI form and associate it with this form
     *
     * @throws Exception - on failure to insert new COI form in database
     * @return int - the id of the new COI form
     */
    public function addCOI() {
        global $db;

        $sql = sprintf("INSERT INTO `forms_coi`
                      (`form_coi_id`, `user_id`, `form_tracking_id`, `created`, `modified`)
                      VALUES(NULL, %s, %s, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())",
                          $this->userId, $this->trackingFormId);
        $result = $db->Execute($sql);
        if (!$result) {
            throw new Exception('Unable to insert new COI form into database');
        }
        else {
            $coiId = mysql_insert_id();
        }

        $this->coi[] = new \tracking\COI($coiId);

        return $coiId;
    }

    public function deleteCoresearcher($userId) {
        global $db;

        // remove the researcher from the database
        $sql = sprintf("DELETE FROM `forms_tracking_coresearchers`
                        WHERE `form_tracking_id` = %s AND `user_id`= %s",
            $this->trackingFormId, $userId);
        $result = $db->Execute($sql);
        if(!$result) {
            throw new Exception('Unable to delete co-researcher');
        }

        // remove the researcher from the array
        foreach($this->coResearchers as $key=>$researcher){
            if($researcher->getUserId() == $userId) {
                unset($this->coResearchers[$key]);
            }
        }
    }

    /**
     * Add a coresearcher to the coreseacher database and associate it with this tracking form
     *
     * @param $userId
     * @param bool $isPI - whether this coresearcher is a PI
     * @throws Exception - on unable to write to database table
     */
    public function addCoresearcher($userId, $isPI = false) {
        global $db;

        // remove existing PI's if this is a PU and they already exist
        if($isPI == true) {
            $this->clearPrincipalInvestigators();
        }

        $sql = sprintf("INSERT INTO `forms_tracking_coresearchers`
                       (`form_tracking_coresearcher_id`,`user_id`,`form_tracking_id`, isPI)
                       VALUES(NULL, %s, %s, %s)",
                $userId, $this->trackingFormId, $isPI ? '1' : '0');
        $result = $db->Execute($sql);
        if(!$result) {
            throw new Exception('Unable to save coresearcher');
        }

        $this->coResearchers[] = new Investigator($userId);
    }

    /**
     * Add an external PI to the tracking form
     *
     * @param $userDetails - array($firstName, $lastName, $phone, $email, $address1, $address2, $address3, $institution)
     * @throws Exception - on unable to write to database table
     */
    public function addExternalPI($userDetails) {
        $this->externalPI = new ExternalInvestigator($this->trackingFormId, true, $userDetails);
    }

    /**
     * Disassociate an external PI
      */
    public function removeExternalPI() {
        if(isset($this->externalPI)) {
            $this->externalPI->remove();
        }
        $this->externalPI = null;
    }

    /**
     * Return this tracking form to the researcher.
     * Note: this removes all pending approvals from the database
     */
    public function returnForm() {
        global $db;

        $this->status = PRESUBMITTED;

        $deanComments ="";  // we save the dean's comments before deleting the approval

        // delete all approvals from database.
        foreach($this->approvals AS $approval) {
            if($approval->type == COMMITMENTS || $approval->type == DEAN_REVIEW) {
                $deanComments = $approval->comments;
            }
            $approval->delete();
        }

        if(strlen($deanComments) > 0) {
            $sql = "UPDATE forms_tracking SET `dean_comments`= '" . $deanComments . "' WHERE  `form_tracking_id` = " . $this->trackingFormId;
            $db->Execute($sql);
        }

        require_once('classes/tracking/notifications/Notifications.php');
        require_once('classes/tracking/notifications/ResearcherNotification.php');

        $subject = sprintf('[TID-%s] Tracking form returned for modifications', $this->trackingFormId);
        $emailBody = sprintf('A tracking form was returned for modifications from your Dean :

Tracking ID : %s
Title : "%s"
Comments : %s

Please make any required modifications and re-submit the form.

', $this->trackingFormId, $this->projectTitle, html_entity_decode(strip_tags($deanComments)));

        $ResearcherNotification = new ResearcherNotification($subject, $emailBody, $this->submitter);
        try {
            $ResearcherNotification->send();
        } catch(Exception $e) {
            printf('Error: Unable to send email notification to Researcher : '. $e);
        }

        $this->saveMe();
    }


    /**
     * Check whether approvals are required for COI
     *
     * @return bool - true if approvals are requires for any COI
     */
    private function COIRequiresApproval()
    {
        global $db;

        // if there is more than one COI form in the db then approvals are required
        $sql = "SELECT * FROM `forms_coi`
                        WHERE `form_tracking_id` = " . $this->trackingFormId . "
                        and `coi_none` = '0'";
        $result = $db->getAll($sql);

        if (count($result) <= 0) {
            return false;
        }

        return true;
    }

    // Determine what files are associated with this tracking form from the database table
    private function loadFiles() {
        global $db;

        $sql = "SELECT * FROM `forms_tracking_files` WHERE `trackingId` = " . $this->trackingFormId;
        $result = $db->getAll($sql);

        return $result;

    }

    /**
     * Determine what files are associated with this tracking form from the directory
     *    Grab the file description from the database
     * @param $dir - the directory the files are located in
     */
    private function loadFilesFromDir($dir) {
        global $db;
        $this->files = array();

        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if($file != '.' && $file != '..') {
                        $size = $this->Size($dir . '/' . $file);
                        $sql = "SELECT `description` FROM `forms_tracking_files` WHERE `trackingId` = " . $this->trackingFormId . " AND `name` = '" . $file . "'";
                        $description = $db->getRow($sql);
                        $description = $description['description'];
                        array_push($this->files, array('name'=>$file, 'size'=>$size, 'description'=>$description));
                    }
                }
                closedir($dh);
            }
        }
    }

    /**
     * function for outputting the size of a file
     *
     * @param $path - the file path
     * @return string - the size of the file
     */
    private function Size($path)
    {
        $bytes = sprintf('%u', filesize($path));

        if ($bytes > 0)
        {
            $unit = intval(log($bytes, 1024));
            $units = array('B', 'KB', 'MB', 'GB');

            if (array_key_exists($unit, $units) === true)
            {
                return sprintf('%d %s', $bytes / pow(1024, $unit), $units[$unit]);
            }
        }

        return $bytes;
    }

    /**
     * Send an email to notify ORS
     */
    private function notifyORS() {
        require_once('classes/tracking/notifications/Notifications.php');
        require_once('classes/tracking/notifications/ORSNotification.php');

        $piDetails = $this->getPIDisplayDetails();
        $piName = $piDetails['firstName'] . " " . $piDetails['lastName'];

        $subject = sprintf('[TID-%s] Tracking form submitted online [%s]', $this->trackingFormId, $piName);
        $emailBody = sprintf('A tracking form was submitted online :

Tracking ID : %s
Title : "%s"
Principal Investigator : %s
Department : %s

', $this->trackingFormId, $this->projectTitle, $piName, $piDetails['departmentName']);

        $ORSNotification = new ORSNotification($subject, $emailBody);
        try {
            $ORSNotification->send();
        } catch(Exception $e) {
            printf('Error: Unable to send email notification to ORS : '. $e);
        }
    }

    /**
     * Send an email to notify of HREB
     */
    private function notifyHREB() {
        require_once('classes/tracking/notifications/Notifications.php');
        require_once('classes/tracking/notifications/HREBNotification.php');

        $piDetails = $this->getPIDisplayDetails();
        $piName = $piDetails['firstName'] . " " . $piDetails['lastName'];
        $subject = sprintf('[TID-%s] Tracking form submitted online [%s]', $this->trackingFormId, $piName);
        $emailBody = sprintf('A tracking form was submitted online :

Tracking ID : %s
Title : "%s"
Principal Investigator : %s
Department : %s

', $this->trackingFormId, $this->projectTitle, $piName, $piDetails['departmentName']);

        $HREBNotification = new HREBNotification($subject, $emailBody);
        try {
            $HREBNotification->send();
        } catch(Exception $e) {
            printf('Error: Unable to send email notification to HREB : '. $e);
        }
    }

    /**
     * Send an email to the Dean
     */
    private function notifyDean() {
        require_once('classes/tracking/notifications/Notifications.php');
        require_once('classes/tracking/notifications/DeanNotification.php');

        $piDetails = $this->getPIDisplayDetails();
        $piName = $piDetails['firstName'] . " " . $piDetails['lastName'];

        $subject = sprintf('[TID-%s] Tracking form submitted online [%s]', $this->trackingFormId, $piName);
        $emailBody = sprintf('A tracking form was submitted online that requires approval :

Tracking ID : %s
Title : "%s"
Principal Investigator : %s
Department : %s

To view full details of this tracking form, please login at http://research.mtroyal.ca and navigate to "My Approvals".

', $this->trackingFormId, $this->projectTitle, $piName, $piDetails['departmentName']);

        $DeanNotification = new DeanNotification($subject, $emailBody, $this);
        try {
            $DeanNotification->send();
        } catch(Exception $e) {
            printf('Error: Unable to send email notification to Dean : '. $e);
        }
    }

    /**
     * Send an email to the Researcher confirming submission of the form
     */
    private function sendSubmissionConfirmationEmail() {
        require_once('classes/tracking/notifications/Notifications.php');
        require_once('classes/tracking/notifications/ResearcherNotification.php');

        $subject = sprintf('[TID-%s] Tracking form submitted successfully', $this->trackingFormId);
        $emailBody = sprintf('Your tracking form was submitted successfully.  It has been assigned a Tracking ID of %s.

Approvals may still be required from your Dean, Ethics, and the Office of Research Services depending on the details provided.  To see details of these approvals, you can login to the MRU research website and go to My Tracking Forms.

If you have any question, then please contact MRU\'s Grants Facilitator, Jerri-Lynne Cameron at 403-440-5081 or jcameron@mtroyal.ca.

Regards,
Office of Research Services

', $this->trackingFormId);

        $confirmationEmail = new ResearcherNotification($subject, $emailBody, $this->submitter);
        try {
            $confirmationEmail->send();
        } catch(Exception $e) {
            printf('Error: Unable to send confirmation notification to researcher : '. $e);
        }
    }

    /**** Variables ****/
    protected $approvals = array();

    protected $trackingFormId;

    protected $deadline;

    protected $createdDate;

    protected $modifiedDate;

    protected $projectTitle = '';

    protected $synopsis = '';

    protected $userId;

    protected $userIsPI = true;

    protected $principalInvestigatorId;

    protected $externalPI;

    protected $coResearcherStudents = false;

    protected $status = PRESUBMITTED;

    protected $funding;

    protected $commitments;

    protected $coi;

    protected $compliance;

    protected $coResearchers = array();

    protected $coResearchersExternal;

    protected $submitter;

    protected $files = array();

}
