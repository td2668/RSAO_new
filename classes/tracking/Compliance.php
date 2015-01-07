<?php
/**
 * Compliance
 */

namespace tracking;

use Exception;

DEFINE('HREB_PENDING', 0);

class Compliance
{

    function __construct($formData)
    {
         $this->trackingFormId = $formData['form_tracking_id'];
         $this->locationMRU = $formData['loc_mru'];
         $this->locationCanada = $formData['loc_canada'];
         $this->locationInternational = $formData['loc_international'];
         $this->locationText = $formData['where'];
         $this->humanBehavioural = $formData['human_b'];
         $this->humanBehaviouralClearance = $formData['human_b_clearance'];
         $this->humanBehaviouralProtocol = $formData['human_b_protocol'];
         $this->humanHealth = $formData['human_h'];
         $this->biohazard = $formData['biohaz'];
         $this->animalSubjects = $formData['animal'];
    }

    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }

        return +$this;
    }

    public function save() {
        global $db;

        $sql = sprintf("UPDATE `forms_tracking` SET
                                `loc_mru` = '%s',
                                `loc_canada` = '%s',
                                `loc_international` = '%s',
                                `where` = '%s',
                                `human_b` = '%s',
                                `human_h` = '%s',
                                `biohaz` = '%s',
                                `animal` = '%s',
                                `human_b_clearance` = '%s',
                                `human_b_protocol` = '%s'
                                WHERE `form_tracking_id` = '%s'",
            $this->locationMRU,
            $this->locationCanada,
            $this->locationInternational,
            $this->locationText,
            $this->humanBehavioural,
            $this->humanHealth,
            $this->biohazard,
            $this->animalSubjects,
            $this->humanBehaviouralClearance,
            $this->humanBehaviouralProtocol,
            $this->trackingFormId
        );
        $result=$db->Execute($sql);

        if(!$result) {
            throw new Exception('Tracking form compliance cannot be saved.');
        }

        // on submit, if HREB required, then set as pending in the HREB table.
  /*      if($this->requiresBehavioural()) {
            $this->saveHREBStatus(HREB_PENDING);
        }*/
    }

    /**
     * Whether behavioural ethics is required
     *
     * @return bool - true of required, false otherwise
     */
    public function requiresBehavioural() {
        $isRequired = $this->humanBehavioural == 1 ? true : false;

        return $isRequired;
    }

    /**
     * Whether health ethics is required
     *
     * @return bool - true of required, false otherwise
     */
    public function requiresHealth() {
        $isRequired = $this->humanHealth == 1 ? true : false;

        return $isRequired;
    }

    /**
     * Whether animal subjects ethics is required
     *
     * @return bool - true of required, false otherwise
     */
    public function requiresAnimal() {
        $isRequired = $this->animalSubjects == 1 ? true : false;

        return $isRequired;
    }

    /**
     * Whether biohazard ethics is required
     *
     * @return bool - true of required, false otherwise
     */
    public function requiresBiohazard() {
        $isRequired = $this->biohazard == 1 ? true : false;

        return $isRequired;
    }

    /**
     * The number of compliance that are required
     *
     * @return int - The number of compliance that are required
     */
    public function getNumRequired() {
        $total = 0;
        if ($this->humanBehavioural == 1)
            $total++;
        if ($this->humanHealth == 1)
            $total++;
        if ($this->biohazard == 1)
            $total++;
        if ($this->animalSubjects == 1)
            $total++;

        return $total;
    }

    /**
     * Determine if a location was specified
     *
     * @return bool - true of a location was specified, false otherwise
     */
    public function locationSpecified() {
        if($this->locationMRU || $this->locationInternational || $this->locationCanada || $this->locationText) {
            return true;
        }

        return false;
    }

    /**
     * Get the HREB status of this tracking form
     *
     * @return string - the HREB status
     */
    public function getHrebStatus() {
        global $db;

        if($this->requiresBehavioural()) {
            $sql = "SELECT st.name FROM hreb
                    LEFT JOIN hreb_status AS st ON hreb.status = st.value
                    WHERE hreb.trackingId = " . $this->trackingFormId;
            $hrebStatus = $db->getRow($sql);
            $hrebStatus = $hrebStatus['name'];
            if($hrebStatus == "") {
                $hrebStatus = "Pending";
            }
        } else {
            return ""; // not declared
        }

        return $hrebStatus;
    }

    /**
     * Get the HREB status of this tracking form
     *
     * @return int - the HREB status code
     */
    public function getHrebStatusCode() {
        global $db;

        if($this->requiresBehavioural()) {
            $sql = "SELECT st.value FROM hreb
                    LEFT JOIN hreb_status AS st ON hreb.status = st.value
                    WHERE hreb.trackingId = " . $this->trackingFormId;
            $hrebStatusCode = $db->getRow($sql);
            $hrebStatusCode = $hrebStatusCode['value'];
        } else {
            return -1;
        }

        return $hrebStatusCode;
    }

    /**
     * Insert or update the HREB status for this tracking form (behavioural only)
     *
     * @param $status - the status of the HREB from the `hreb_stauts` table
     * @throws \Exception - on unable to save status
     */
    private function saveHREBStatus($status) {
        global $db;

        $sql = sprintf("INSERT INTO hreb (`trackingId`, `status`) VALUES (%s, %s) ON DUPLICATE KEY UPDATE `status` = %s", $this->trackingFormId, $status, $status);
        $result=$db->Execute($sql);

        if(!$result) {
            throw new Exception('Tracking form hreb status cannot be saved.');
        }
    }

    protected $trackingFormId;
    protected $locationMRU;
    protected $locationCanada;
    protected $locationInternational;
    protected $locationText;
    protected $humanBehavioural;
    protected $humanBehaviouralClearance;
    protected $humanBehaviouralProtocol;
    protected $humanHealth;
    protected $biohazard;
    protected $animalSubjects;

}
