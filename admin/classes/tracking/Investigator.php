<?php

namespace tracking;


require_once('includes/global.inc.php');

/**
 * Investigator involved in the research
 */
class Investigator
{

    /**
     * The constructor
     */
    function __construct($userId, $isPI = false)
    {
        $this->userId = $userId;
        $investigator = $this->getInvestigatorByUserId();
        $this->firstName = $investigator['first_name'];
        $this->lastName = $investigator['last_name'];
        $this->displayName = $this->lastName . ", " . $this->firstName;
        $this->phone = $investigator['phone'];
        $this->email = $investigator['email'];
        $this->departmentName = $investigator['departmentName'];
        $this->isPI = $isPI;
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

    /**
     * Get the investigator by userID
     */
    protected function getInvestigatorByUserId() {
        global $db;

        $sql = sprintf("SELECT users.first_name, users.last_name, profiles.phone, profiles.email, departments.name AS departmentName
                        FROM users
                        LEFT JOIN profiles ON (users.user_id = profiles.user_id)
                        LEFT JOIN departments ON (users.department_id = departments.department_id)
                        WHERE users.`user_id`= %s", $this->userId);
        $investigator = $db->getRow($sql);


        return $investigator;
    }

    public function getDisplayName()
    {
        return $this->displayName;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    protected $isPI = false;

    protected $userId;

    protected $firstName;

    protected $lastName;

    protected $displayName;

    protected $phone;

    protected $email;

    protected $address;

    protected $departmentName;
}
