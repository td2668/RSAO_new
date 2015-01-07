<?php

namespace tracking;

use Exception;

require_once('includes/global.inc.php');
/**
 * Funding associated with a tracking form or project
 **/

class Funding
{

    function __construct($formData)
    {
        $this->trackingId = $formData['form_tracking_id'];
        $this->hasFunding = $formData['funding'] == 1 ? true : false;
        $this->requiresLetter = $formData['letter_required'] == 1 ? true : false;
        $this->orsSubmits = $formData['ors_submits'] == 1 ? true : false;
        $this->fundingDeadline = $formData['funding_deadline'];
        $this->grantType = $formData['grant_type'];
        $this->agency_id = $formData['agency_id'] ? $formData['agency_id'] : 0 ;
        $this->agency_name = $formData['agency_name'];
        $this->program_id = $formData['program_id'];
        $this->funding_confirmed = $formData['funding_confirmed'] ? true : false;
        $this->requested = $formData['requested'];
        $this->received = $formData['received'];
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

    public function save() {
        global $db;

        $sql = sprintf(
            "UPDATE `forms_tracking` SET
                      `funding`  = '%s',
                      `letter_required`  = '%s',
                      `funding_deadline` = '%s',
                      `ors_submits` = '%s',
                      `grant_type` = %s,
                      `agency_id`  = '%s',
                      `agency_name` = '%s',
                      `program_id` =  '%s',
                      `funding_confirmed` = '%s',
                      `requested` =  '%s',
                      `received` =  '%s'
                  WHERE `form_tracking_id` =  '%s'",
            $this->hasFunding,
            $this->requiresLetter == true ? "1" : "0",
            $this->fundingDeadline,
            $this->orsSubmits == true ? "1" : "0",
            $this->grantType,
            $this->agency_id,
            mysql_real_escape_string($this->agency_name),
            $this->program_id,
            $this->funding_confirmed,
            $this->requested,
            $this->received,
            $this->trackingId
        );
        $result=$db->Execute($sql);

        if(!$result) {
            throw new Exception('Tracking form funding details cannot be saved.');
        }
    }

    /**
     * Retrieve the list of Agencies from the database as an HTML select list
     *
     * @throws \Exception on failure to retrieve agencies from database
     */
    public function getAgencyOptions() {
        global $db;

        $sql = "SELECT * from `ors_agency` WHERE 1 ORDER BY `name`";
        $agencies = $db->getAll($sql);

        if (!$agencies) {
            throw new Exception('Unable to retrieve agencies from database');
        }

        $agency_options = '';
        foreach ($agencies as $agency) {
            if ($this->agency_id == $agency['id']) {
                $selected = 'selected';
            } else {
                $selected = '';
            }
            $agency_options .= "<option value='$agency[id]' $selected>$agency[name]</option>\n";
        }
        return $agency_options;
    }

    /**
     * Get the agency programs from the database as an HTML select list
     */
    public function getProgramOptions() {
        global $db;

        $program_options = '';
        if ($this->agency_id > 0) {
            $sql = "SELECT * from `ors_program` WHERE `ors_agency_id` = {$this->agency_id} ORDER BY `name`";
            $programs = $db->getAll($sql);

            if (!$programs) {
                // no associated programs
                $program_options = "";
            } else {
                foreach ($programs as $program) {
                    if ($this->program_id == $program['id']) {
                        $selected = 'selected';
                    } else {
                        $selected = '';
                    }
                    $program_options .= "<option value='$program[id]' $selected>$program[name]</option>\n";
                }
            }
        }
        return $program_options;
    }

    public function hasFunding()
    {
        return $this->hasFunding == 'on' ? true : false;
    }

    /**
     * Whether the agency has been entered as text rather then selected from our list
     *
     * @return bool - Whether the agency has been entered as text rather then selected from our list
     */
    public function hasCustomAgency() {
        if($this->agency_id == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the name of the Agency associated with this funding
     *
     * @return string - the agency name as a string
     */
    public function getAgency() {
        if($this->hasCustomAgency() == true) {
            return $this->agency_name;
        } else {
            return $this->getAgencyName($this->agency_id);
        }
    }

    /**
     * Get the name of the Program associated with this funding
     *
     * @return string - the program name as a string
     */
    public function getProgram() {
        if($this->program_id > 0) {
            return $this->getProgramName($this->program_id);
        } else {
            return '';
        }
    }

    /**
     * @param $agencyId - the agency ID
     * @return string - the agency name
     * @throws \Exception - on unable to retrieve agency
     */
    private function getAgencyName($agencyId) {
        global $db;

        $sql = "SELECT * from `ors_agency` WHERE`id` = " . $agencyId;
        $agency = $db->getRow($sql);

        if (!$agency) {
            throw new Exception('Unable to retrieve agency from database');
        }

        return $agency['name'];
    }

    /**
     * @param $programId - the program ID
     * @return string - the program name
     * @throws \Exception - on unable to retrieve program
     */
    private function getProgramName($programId) {
        global $db;

        $sql = "SELECT * from `ors_program` WHERE `id` = " . $programId;
        $program = $db->getRow($sql);

        if (!$program) {
            throw new Exception('Unable to retrieve program from database');
        }

        return $program['name'];
    }

    protected $grantType = 0;
    protected $hasFunding = false;
    protected $requiresLetter = false;
    protected $orsSubmits = false;
    protected $fundingDeadline;
    protected $trackingId;
    protected $agency_id;
    protected $agency_name;
    protected $program_id;
    protected $funding_confirmed;
    protected $requested;
    protected $received;
}
