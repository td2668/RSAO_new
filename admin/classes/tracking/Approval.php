<?php

namespace tracking;

use Exception;
use DateTime;
use tracking\notifications\ResearcherNotification;

/* Approval Types */
define('COMMITMENTS', 1);
define('COI', 2);
define('ETHICS_BEHAVIOURAL', 3);
define('ETHICS_HEALTH', 4);
define('ETHICS_ANIMAL', 5);
define('ETHICS_BIOHAZARD', 6);
define('DEAN_REVIEW', 7);
define('ORS_REVIEW', 8);


/* Approval Status */
define('PRESUBMITTED', 0);
define('APPROVED', 1);

/**
 * An approval for a tracking form
 */
abstract class Approval
{
    function __construct($trackingFormId)
    {
        $this->trackingFormId = $trackingFormId;
        $this->status = PRESUBMITTED;
        $this->comments = "";
    }

    /** Change the status to Complete (i.e. it has been approved)
           and set the time approved to now.
     *
     * @var $sendMail bool = whether to send an email confirmation of the approval or not.
    */
    public function approve($sendMail = true) {
        $this->status = APPROVED;
        $this->dateApproved = new DateTime('now');

        if($sendMail == true) {
            $this->sendResearcherNotification();
        }
    }

    /* Get the display name for this approval */
    public function getFriendlyName() {

        global $db;
        $name = '';

        if (isset($this->type)) {
            $sql = "SELECT friendlyName FROM forms_approval_type
                WHERE id = " . $this->type;

            $result = $db->getRow($sql);
            $name = $result['friendlyName'];
        }
        else {
            throw new Exception('Approval type is not defined.');
        }

        return $name;
    }

    /**
     * Save the approval in the database.
     *
     * @throws Exception - on unable to insert approval into database
     */
    public function save($divisionId) {
        global $db;

        if(!isset($divisionId)) {
            $divisionId = 23;  // Assign Research Services as the division if the user has none assigned
        }

      // check to see if the approval already exists in the table first
      $sql = sprintf("SELECT * FROM  `forms_tracking_approvals`
                      WHERE `tracking_id` = %s AND `approval_type_id` = %s",
             $this->trackingFormId, $this->type);
      $result = $db->GetAll($sql);

       // we either do an insert or an update depending on whether the entry already exists
      if(count($result) == 0) {
        $sql = sprintf(
                "INSERT INTO `forms_tracking_approvals`
                     (`tracking_id`,
                      `approval_type_id`,
                      `division_id`,
                      `comments`,
                      `approved`,
                      `date_submitted`,
                      `date_approved`)
                VALUES(%s, %s, %s, '%s', %s, NOW(), NULL)",
                $this->trackingFormId, $this->type, $divisionId, $this->comments, $this->status);

        $result = $db->Execute($sql);

        if(!$result) {
            return -2;
        }
      } else {
          $sql = sprintf("UPDATE `forms_tracking_approvals`
                          SET approved = %s, date_approved = NOW(), division_id = %s, comments = '%s'
                          WHERE tracking_id = %s
                            AND approval_type_id = %s", $this->status, $divisionId, $this->comments, $this->trackingFormId, $this->type);

          $result = $db->Execute($sql);

          if(!$result) {
              return -3;
          }
      }

        return 1;
    }

    /**
     * Delete this approval from the database
     */
    public function delete() {
        global $db;

        $sql = sprintf("DELETE FROM `forms_tracking_approvals`
                        WHERE tracking_id = %s AND approval_type_id = %s",
                $this->trackingFormId, $this->type);

        $result = $db->Execute($sql);

        if(!$result) {
            printf('Unable to delete approval from database');
        }
    }

    /**
     * Send an email notification to the researcher that an approval has occurred
     *      - The actual body of the notification gets generated in the inherited classes that implement ApprovalNotification .
     */
    protected function sendResearcherNotification() {
        require_once('classes/tracking/notifications/Notifications.php');
        require_once('classes/tracking/notifications/ResearcherNotification.php');

        // get the submitter details from the tracking form
        $trackingForm = new TrackingForm();
        $form = $trackingForm->retrieveForm($this->trackingFormId);
        $submitter = $trackingForm->submitter;

        $emailSubject = $this->getEmailSubject($trackingForm);
        $emailBody =  $this->getEmailBody($trackingForm);

        if(strlen($emailBody) > 0 && strlen($emailSubject) > 0) {
            $researcherNotification = new ResearcherNotification($emailSubject, $emailBody, $submitter);
            try {
                $researcherNotification->send();
            } catch(Exception $e) {
                printf('Error: Unable to send approval email notification to Researcher : '. $e);
            }
        }
    }

    /**
     * Get the comments in plain-text form
     *
     * @return string - comments with HTML formatting removed
     */
    protected function getPlainTextComments(){

        return html_entity_decode(strip_tags($this->comments));
    }

    /**
     *  Get email subject - Implemented in inherited class
     *
     * @param $trackingForm
     * @return string - the email subject
     */
    abstract protected function getEmailSubject($trackingForm);

    /**
     * Get email body - Implemented in inherited class
     *
     * @param $trackingForm
     * @return string - the email body
     */
    abstract protected function getEmailBody($trackingForm);

    /**
     * The type of approval
     *
     * @var int ApprovalType
     */
    public $type;

    /**
     * The name of the approval (approval_type)
     *
     * @var string $name
     */
    public $name;

    /**
     * Whether the approval has been made
     *
     * @var bool $status
     */
    public $status;

    /**
     * The date the approval occurred
     *
     * @var DateTime - $dateApproved
     */
    public $dateApproved;

    /**
     * The date the approval occurred
     *
     * @var DateTime - $dateApproved
     */
    public $dateSubmitted;

    /**
     * Any comments associated with the approval
     *
     * @var string - $comments
     */
    public $comments;

    /**
     * The tracking form ID
     *
     * @var int - $trackingFormId
     */
    protected $trackingFormId;

}