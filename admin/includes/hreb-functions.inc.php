<?php
/**
 * Functions to support the HREB mdoule
 * User: ischuyt
 * Date: 20/10/13
 * Time: 1:45 PM
 */

DEFINE('STATUS_UPDATE', 4);
DEFINE('EXPIRY_CHANGE', 5);
DEFINE('NEW_REVISION', 7);


/**
 * Generates an ethics number based on the max in the table of the format: YYYY-XX
 *
 * YYYY = current year
 * XX = ID number
 *
 * @return string - The new ethics number YYYY-XX
 */
function generateNewEthicsNumber() {
    global $db;

    $year = date("Y");
    $sql = "SELECT MAX(ethicsnum) AS maxId FROM hreb";
    $result = $db->getRow($sql);
    $maxId = $result['maxId'];
    $ethicsNum = explode("-", $maxId);
    $ethicsNum[1]++;

    // prepend a 0 to single digits
    if(strlen($ethicsNum[1]) == 1) {
        $ethicsNum[1] = "0" . $ethicsNum[1];
    }

    return $year . "-" . $ethicsNum[1];
}

/**
 * Fetches info on a Tracking form and returns the info as a JSON
 */
function getTrackingJSON($trackingId)
{
    $trackingForm = getTrackingForm($trackingId);
    if(sizeof($trackingForm) == 0) {
        // no tracking form with that ID
    } else {
        return json_encode($trackingForm);
    }
}

/**
 * Get a tracking form from the database
 *
 * @param $trackingId - the tracking form ID
 * @return array - The tracking form fields from the DB
 */
function getTrackingForm($trackingId) {
    global $db;

    $sql = "SELECT tracking.*, tracking.pi AS isPI, DATE_FORMAT(tracking.submit_date,'%b %D, %Y') AS niceSubmittedDate,
            CONCAT(u1.first_name, ' ', u1.last_name) AS applicant,
            CONCAT(u2.first_name, ' ', u2.last_name) AS pi,
            CONCAT(tracking.firstname, ' ', tracking.lastname) AS piexternal,
            d1.name AS applicantDept,
            d2.name AS piDept
            FROM forms_tracking AS tracking
            LEFT JOIN users u1 ON tracking.user_id = u1.user_id
            LEFT JOIN users u2 ON tracking.pi_id = u2.user_id
            LEFT JOIN departments d1 ON u1.department_id = d1.department_id
            LEFT JOIN departments d2 ON u2.department_id = d2.department_id
            WHERE form_tracking_id = " . $trackingId;
    $result = $db->getRow($sql);

    return $result;
}

function saveModification($modification){
//Todo - Save modification
/*    global $db;

    $sql = "INSERT INTO hreb_updates VALUES ()";*/
}

/**
 * Save the ethics applications
 *
 * @param $post - the post values
 * @return bool - true of successful, false otherwise
 */
function saveEthics($post) {
    global $db;

    $ethicsNum = mysql_real_escape_string($post['ethicsNumber']);

    // check if we're doing an update or an insert
    $sql = "SELECT COUNT(*) FROM hreb WHERE ethicsNum = '{$ethicsNum}'";
    $isUpdate = $db->GetRow($sql);

    if($isUpdate['COUNT(*)'] == 1) {
        $sql = "UPDATE `hreb` SET ethicsnum   = '{$post['ethicsNumber']}',
                                 trackingId   = {$post['form_tracking_id']},
                                 app_received = '{$post['received']}',
                                 expiryDate   = '{$post['expiry']}'
                WHERE hreb.ethicsnum = '{$ethicsNum}'";
    } else {
        $sql = "INSERT into `hreb` (ethicsnum, trackingId, app_received, expiryDate)
                VALUES ('{$ethicsNum}', {$post['form_tracking_id']}, '{$post['received']}', '{$post['expiry']}')";
    }

    try{
        $db->Execute($sql);
    } catch(Exception $e){
        echo "Error saving entry to database : " . $e->getMessage();
        return false;
    }
    return true;
}

/**
 * Get all the update types
 *
 * @return array - All of the hreb types
 */
function getAvailableUpdates() {
    global $db;

    $sql = "SELECT id, friendlyName AS name FROM hreb_update_type WHERE `visible` = 1 ORDER BY `type`, `name` ASC";
    $result = $db->getAll($sql);

    return $result;
}


/**
 * Class hreb - An HREB applications
 */
class hreb {

    function __construct($ethicsNum)
    {
        $this->ethicsNumber = $ethicsNum;
        $this->getEthics();
    }

    /**
     * Load the object with data of an ethics application from the database
     *
     */
    protected function getEthics() {
        global $db;

        $sql = "SELECT hreb.*, tf.tracking_name, tf.pi AS isPI, tf.user_id, tf.pi_id,
                CONCAT(u1.first_name, ' ', u1.last_name) AS applicant,
                CONCAT(u2.first_name, ' ', u2.last_name) AS pi,
                CONCAT(tf.firstname, ' ', tf.lastname) AS piexternal,
                d1.name AS applicantDept,
                d2.name AS piDept
                FROM hreb
                LEFT JOIN forms_tracking AS tf ON hreb.trackingId = tf.form_tracking_id
                LEFT JOIN users u1 ON tf.user_id = u1.user_id
                LEFT JOIN users u2 ON tf.pi_id = u2.user_id
                LEFT JOIN departments d1 ON u1.department_id = d1.department_id
                LEFT JOIN departments d2 ON u2.department_id = d2.department_id
                WHERE ethicsnum = '" . mysql_real_escape_string($this->ethicsNumber) . "'";

        $result = $db->getRow($sql);
        $this->revision = $result['revision'];
        $this->form_tracking_id = $result['trackingId'];
        $this->tracking_name = $result['tracking_name'];
        $this->received = $result['app_received'];

        // Logic to determine applicant based on PI flag
        if($result['isPI']) {
            $applicant = $result['applicant'];
            $department = $result['applicantDept'];
        } else {
            if($result['pi_id'] == 0) {
                $applicant = $result['piexternal'];
                $department = 'External';
            } else {
                $applicant = $result['pi'];
                $department = $result['piDept'];
            }
        }
        $this->applicant = $applicant;
        $this->department = $department;

        // Load the modifications
        $sql = "SELECT id
                FROM hreb_updates
                WHERE ethicsNum = '" . mysql_real_escape_string($this->ethicsNumber) . "' ORDER BY dateCreated DESC";
        $result = $db->getAll($sql);

        if(count($result) > 0) {
            foreach($result AS $mod) {
                $this->mods[] = new Modification($mod['id']);
            }
        }
    }

    /*
     * Return the list of modifications as an array of values
     */
    public function getModsAsArray() {
        $modArray = array();
        foreach($this->mods AS $mod){
            $values = get_object_vars($mod);
            $phpdate = strtotime( $values['dateCreated'] );
            $date = date( 'Y-m-d', $phpdate );
            $time = date( 'h:i A', $phpdate );
            $values['dateCreated'] = $date;
            $values['timeCreated'] = $time;
            $modArray[] = $values;
        }

        return $modArray;
    }

    /*
     * Return the list of modifications as a JSON
     */
    public function getModsAsJSON() {
        $modArray = array();
        foreach($this->mods AS $mod){
            $values = get_object_vars($mod);
            $phpdate = strtotime( $values['dateCreated'] );
            $date = date( 'Y-m-d', $phpdate );
            $time = date( 'h:i A', $phpdate );
            $values['dateCreated'] = $date;
            $values['timeCreated'] = $time;
            $modArray[] = $values;
        }

        return $modArray;
    }

    /**
     * Update the status of the application
     *
     * @param $statusId - the new statusId
     * @param bool $appendNote - If true, then a note will be appended to the ethics modifications log
     */
    public function updateStatus($statusId, $appendNote = true) {
        global $db;

        $status = mysql_real_escape_string($statusId);
        $sql = sprintf("UPDATE hreb SET `status` = %s WHERE `ethicsNum` = '%s'", $status, $this->ethicsNumber);
        try {
            $db->Execute($sql);
        } catch(Exception $e) {
            echo "Unable to update ethics status : " . $e->getMessage();
            $appendNote = false;
        }

        if($appendNote == true) {
            // add a modification note to the record
            $mod = new StatusUpdate_Modification($this->ethicsNumber, $statusId);
            $mod->save();
        }
    }

    /**
     * Update the expiry date of the application
     *
     * @param $expiryDate - the expiry date
     * @param bool $appendNote - If true, then a note will be appended to the ethics modifications log
     */
    public function updateExpiryDate($expiryDate, $appendNote = true) {
        global $db;

        $expiryDate = mysql_real_escape_string($expiryDate);
        $sql = sprintf("UPDATE hreb SET `expiryDate` = '%s' WHERE `ethicsNum` = '%s'", $expiryDate, $this->ethicsNumber);

        try {
            $db->Execute($sql);
        } catch(Exception $e) {
            echo "Unable to update ethics expiry date  : " . $e->getMessage();
            $appendNote = false;
        }

        if($appendNote == true) {
            // add a modification note to the record
            $mod = new ExpiryUpdate_Modification($this->ethicsNumber, $expiryDate);
            $mod->save();
        }
    }

    /**
     * Increment the revision number
     *
     * @param bool $appendNote - If true, then a note will be appended to the ethics modifications log
     */
    public function newRevision($appendNote = true) {
        global $db;

        $revision = $this->revision;

        if($revision == '') {
            $revision = 'a';
        } else {
            $revision = ++$revision;
        }

        $sql = "UPDATE `hreb` SET `revision` = '" . $revision . "' WHERE `ethicsnum` = '" . $this->ethicsNumber ."'";
        $db->Execute($sql);

        if($appendNote == true) {
            // add a modification note to the record
            $mod = new NewRevision_Modification($this->ethicsNumber, $revision);
            $mod->save();
        }
    }

    /**
     * Delete this applications
     *    Note: this sets the 'deleted' flag = 1 and doesn't actually remove the application from the database
     *
     * @retun - true if successful, false otherwise
     */
    public function delete()
    {
        global $db;

        $sql = "UPDATE `hreb` SET `deleted` = 1 WHERE `ethicsnum` = '" . $this->ethicsNumber . "'";

        try {
            $db->Execute($sql);
        } catch(Exception $e) {
            echo "Unable to delete ethics application from database : " . $e->getMessage();
            return false;
        }

        return true;
    }

    /* Object Variables */
    public $ethicsNumber;
    public $revision;
    public $form_tracking_id;
    public $tracking_name;
    public $received;
    public $applicant;
    public $department;
    public $mods = array();

}

class Modification {

    /**
     * Constructor
     *
     * @param $id - the DB id of the modification.
     *   if id = 0 then a blank object is created
     *   if id != 0 then values will attempt to be loaded from the DB with the given ID
     */
    function __construct($id = 0)
    {
        global $db;

        // Load the friendly names from the DB and store them for reference
        $sql = "SELECT * FROM hreb_update_type";
        $updateTypes = $db->getAll($sql);
        foreach($updateTypes AS $updateType) {
            $this->friendlyNames[$updateType['id']] = array('typeFriendly'=> $updateType['type'],
                                                            'friendlyName' => $updateType['friendlyName']);
        }

        if($id != 0) {
            $this->id = $id;
            $this->getModification();
        }
    }

    /**
     * Load the object with data of an ethics application from the database
     *
     */
    protected function getModification() {
        global $db;

        $sql = "SELECT *
                FROM hreb_updates
                LEFT JOIN hreb_update_type AS ut ON hreb_updates.typeId = ut.id
                WHERE hreb_updates.id = " . mysql_real_escape_string($this->id);

        $result = $db->getRow($sql);

        $this->typeId = $result['type'];
        $this->ethicsNumber = $result['ethicsNum'];
        $this->friendlyName = $result['friendlyName'];
        $this->dateCreated = $result['dateCreated'];
        $this->note = $result['note'];
    }

    /**
     * Save the modification to the database
     */
    public function save() {
        global $db;

        // If dateCreated is empty, then set it to the current date
        if($this->dateCreated == '' || $this->dateCreated == NULL) {
            $this->dateCreated = date('Y-m-d H:i:s');
        }

        $sql = "INSERT INTO `hreb_updates` (`ethicsNum`, `typeId`, `dateCreated`, `note`)
                VALUES ('{$this->ethicsNumber}', {$this->typeId}, '{$this->dateCreated}', '{$this->note}')";
        try {
            $db->Execute($sql);
        } catch (Exception $e) {
            echo "Unable to save modification to database : " . $e->getMessage();
        }
    }

    /**
     * Delete this modification from the database
     */
    public function delete()
    {
        global $db;

        $sql = "DELETE FROM `hreb_updates` WHERE id = $this->id";
        try {
            $db->Execute($sql);
        } catch (Exception $e) {
            echo "Unable to delete modification from database : " . $e->getMessage();
        }
    }

    /**
     * Initialize the class values from a JSON array
     *
     * @param $json - the json data
     */
    public function initializeFromJSON($json) {
        $this->typeId = $json['category'];
        $this->ethicsNumber = $json['ethicsNum'];
        $this->dateCreated = $json['dateAdded'];
        $this->note = mysql_real_escape_string($json['notes']);
        $this->friendlyName = $this->friendlyNames[$this->typeId]['friendlyName'];
        $this->typeFriendly =  $this->friendlyNames[$this->typeId]['typeFriendly'];
    }

    public $id;
    public $ethicsNumber;
    public $typeId;
    public $typeFriendly;
    public $dateCreated;
    public $note;
    public $friendlyName;

    private $friendlyNames = array();  //friendly names of all modifications for reference

}

class StatusUpdate_Modification extends Modification {

    function __construct($ethicsNum, $statusId) {
        global $db;

        $this->ethicsNumber = $ethicsNum;
        $this->note = $statusId;
        $this->dateCreated = date('Y-m-d H:i:s');
        $this->typeId = STATUS_UPDATE;

        $sql = "SELECT name FROM hreb_status WHERE value = " . $statusId;

        try {
            $result = $db->getRow($sql);
        } catch (Exception $e) {
            echo "Unable to retrieve ethics status string : " . $e->getMessage();
        }

        $this->note = "Status Changed to : " . $result['name'];
    }
}

class ExpiryUpdate_Modification extends Modification {

    function __construct($ethicsNum, $expiryDate) {

        $this->ethicsNumber = $ethicsNum;
        $this->dateCreated = date('Y-m-d H:i:s');
        $this->typeId = EXPIRY_CHANGE;

        $this->note = "Expiry Date changed to : " . $expiryDate;
    }
}

class NewRevision_Modification extends Modification {

    function __construct($ethicsNum, $revision) {

        $this->ethicsNumber = $ethicsNum;
        $this->typeId = NEW_REVISION;

        $this->note = "------  Revision " . $revision . " ------";
    }
}