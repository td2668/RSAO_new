<?php
/**
 * Conflict of Interest
 */

namespace tracking;

use Exception;

class COI
{
    function __construct($coiId)
    {
        global $db;

        if (isset($coiId)) {
            $this->coiId = $coiId;
            if ($coiId > 0) {
                $sql = sprintf("SELECT * FROM `forms_coi`
                                LEFT JOIN `users` using (`user_id`)
                                WHERE `form_coi_id`= %s
                                ", $coiId);
                $coi = $db->getRow($sql);

                if (!$coi) {
                    throw new Exception('Unable to retrieve COI from database using coi_form_id = ' . $coiId);
                } else {
                    $this->name = $coi['name'];
                    $this->user_id = $coi['user_id'];
                    $this->coi_none = $coi['coi_none'];
                    $this->coi01 = $coi['coi01'];
                    $this->coi02 = $coi['coi02'];
                    $this->coi03 = $coi['coi03'];
                    $this->coi04 = $coi['coi04'];
                    $this->coi05 = $coi['coi05'];
                    $this->coi06 = $coi['coi06'];
                    $this->coi07 = $coi['coi07'];
                    $this->coi08 = $coi['coi08'];
                    $this->coi09 = $coi['coi09'];
                    $this->coi10 = $coi['coi10'];
                    $this->coi11 = $coi['coi11'];
                    $this->relationship = $coi['relationship'];
                    $this->situation = $coi['situation'];
                    $this->coi_other = $coi['coi_other'];
                    $this->financial = $coi['financial'];
                    $this->modified = $coi['modified'];
                    $this->last_name = $coi['last_name'];
                    $this->first_name = $coi['first_name'];
                }
            }
        } else {
            throw new Exception('Conflict of interest ID not specified.  Unable to create.');
        }
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

        $sql = sprintf("UPDATE  `forms_coi` SET
            `name` = '%s',
            `user_id` = '%s',
            `coi_none` = '%s',
            `coi01` = '%s',
            `coi02` = '%s',
            `coi03` = '%s',
            `coi04` = '%s',
            `coi05` = '%s',
            `coi06` = '%s',
            `coi07` = '%s',
            `coi08` = '%s',
            `coi09` = '%s',
            `coi10` = '%s',
            `coi11` = '%s',
            `relationship` = '%s',
            `situation` = '%s',
            `coi_other` = '%s',
            `financial` = '%s',
            `modified` = UNIX_TIMESTAMP(NOW())
            WHERE `form_coi_id` = '%s'
            ",
            $this->name,
            $this->user_id,
            $this->coi_none,
            $this->coi01,
            $this->coi02,
            $this->coi03,
            $this->coi04,
            $this->coi05,
            $this->coi06,
            $this->coi07,
            $this->coi08,
            $this->coi09,
            $this->coi10,
            $this->coi11,
            mysql_real_escape_string($this->relationship),
            mysql_real_escape_string($this->situation),
            $this->coi_other,
            $this->financial,
            $this->coiId
        );

        $result = $db->Execute($sql);

        if(!$result) {
            throw new Exception('Tracking form conflict of interests cannot be saved.');
        }
    }

    public function delete() {
        global $db;

        $sql= sprintf("DELETE FROM `forms_coi` WHERE `form_coi_id`='%s'", $this->coiId);

        $result = $db->Execute($sql);
        if(!$result) {
            throw new Exception('Unable to delete COI form with ID = ' . $this->coiId);
        }
    }

    public function hasDeclarations() {
        return $this->coi_none == 1 ? false : true;
    }

    /**
     * Get a string version of any declarted COI's
     */
    public function getDeclarations() {
        $cois = array();

        if($this->coi01 == 1) {$cois['coi01'] = 'Interest in a research, business, contract or transaction'; };
        if($this->coi02 == 1) {$cois['coi02'] = 'Influencing purchase of equipment, materials or services '; };
        if($this->coi03 == 1) {$cois['coi03'] = 'Acceptance of gifts, benefits or financial favours'; };
        if($this->coi04 == 1) {$cois['coi04'] = 'Use of information '; };
        if($this->coi05 == 1) {$cois['coi05'] = 'Use of students, university personnel, resources or assets'; };
        if($this->coi06 == 1) {$cois['coi06'] = 'Involvement in personnel decisions '; };
        if($this->coi07 == 1) {$cois['coi07'] = 'Evaluation of academic work'; };
        if($this->coi08 == 1) {$cois['coi08'] = 'Academic program decisions'; };
        if($this->coi09 == 1) {$cois['coi09'] = 'Favouring outside interests for personal gain '; };
        if($this->coi10 == 1) {$cois['coi10'] = 'Relationship'; };
        if($this->coi11 == 1) {$cois['coi11'] = 'Undertaking of outside activity'; };
        if($this->$coi_other == 1) {$cois['other'] = 'Other'; };

        return $cois;
    }

    /**
     * Get the text description entered for relationship and situation
     *
     * @return array - The text for relationship and situation
     */
    public function getText() {
        $coiText = array();

        $coiText['Describe the relationship: '] = $this->relationship;
        $coiText['Describe the situation: '] = $this->situation;

        return $coiText;
    }

    protected $coiId;
    protected $name;
    protected $user_id;
    protected $coi_none;
    protected $coi01;
    protected $coi02;
    protected $coi03;
    protected $coi04;
    protected $coi05;
    protected $coi06;
    protected $coi07;
    protected $coi08;
    protected $coi09;
    protected $coi10;
    protected $coi11;
    protected $coi_other;
    protected $financial;
    protected $relationship;
    protected $situation;
    protected $modified;
    protected $last_name;
    protected $first_name;
}

