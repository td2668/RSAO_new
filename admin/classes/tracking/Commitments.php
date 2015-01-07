<?php

namespace tracking;

/**
 * The commitments associated with a tracking form
 */
use Exception;

class Commitments
{
    function __construct($formData)
    {
        $this->trackingFormId             = $formData['form_tracking_id'];
        $this->equipment                  = $formData['equipment_flag'];
        $this->equipmentSummary           = $formData['equipment'];
        $this->space                      = $formData['space_flag'];
        $this->spaceSummary               = $formData['space'];
        $this->other                      = $formData['commitments_flag'];
        $this->otherSummary               = $formData['commitments'];
        $this->employed                   = $formData['employ_flag'];
        $this->employedStudents           = $formData['emp_students'];
        $this->employedResearchAssistants = $formData['emp_ras'];
        $this->employedConsultants        = $formData['emp_consultants'];
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

    public function save()
    {
        global $db;

        $sql = sprintf(
            "UPDATE `forms_tracking` SET
                      `equipment_flag`  = '%s',
                      `equipment`  = '%s',
                      `space_flag` = '%s',
                      `space` =  '%s',
                      `commitments_flag` = '%s',
                      `commitments` =  '%s',
                      `employ_flag` =  '%s',
                      `emp_students` = '%s',
                      `emp_ras` =  '%s',
                      `emp_consultants` =  '%s'
                  WHERE `form_tracking_id` =  '%s'",
            $this->equipment,
            mysql_real_escape_string($this->equipmentSummary),
            $this->space,
            mysql_real_escape_string($this->spaceSummary),
            $this->other,
            mysql_real_escape_string($this->otherSummary),
            $this->employed,
            $this->employedStudents,
            $this->employedResearchAssistants,
            $this->employedConsultants,
            $this->trackingFormId
        );

        $result=$db->Execute($sql);

        if(!$result) {
            throw new Exception('Tracking form commitments cannot be saved.');
        }
    }

    /**
     * @return bool - true if approvals are requires for any commitments
     */
    public function requiresApproval() {
        if($this->equipment || $this->space || $this->other || $this->employed) {
            return true;
        } else {
            return false;
        }
    }

    protected $trackingFormId;
    protected $equipment;
    protected $equipmentSummary;
    protected $space;
    protected $spaceSummary;
    protected $other;
    protected $otherSummary;
    protected $employed;
    protected $employedStudents;
    protected $employedResearchAssistants;
    protected $employedConsultants;
}
