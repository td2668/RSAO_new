<?php

namespace tracking;


require_once('includes/global.inc.php');
/**
 * An investigator that is external to MRU
 *      This could be a Principal Researcher, or a coResearcher.
*       - $this->isPI indicates if it is a Principal researcher
 */
class ExternalInvestigator
{

    /**
     * @param $trackingFormId - the tracking form ID
     * @param $isPI - is this a primary investigator?
     * @param $userDetails - array($firstName, $lastName, $phone, $email, $address1, $address2, $address3, $institution)
     */
    function __construct($trackingFormId, $isPI, $userDetails)
    {
        $this->trackingFormId = $trackingFormId;
        $this->isPI = $isPI;
        $this->firstName = $userDetails['firstName'];
        $this->lastName = $userDetails['lastName'];
        $this->phone = $userDetails['phone'];
        $this->email = $userDetails['email'];
        $this->address1 = $userDetails['address1'];
        $this->address2 =  $userDetails['address2'];
        $this->address3 = $userDetails['address3'];
        $this->institution = $userDetails['institution'];

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

        if($this->isPI) {

            $this->remove();  //remove any entries already associated with this tracking form - 1 PI per form

            $sql = sprintf("INSERT INTO `research`.`forms_tracking_external_pi`
                          (`tracking_id`, `firstName`, `lastName`, `phone`, `email`, `address1`, `address2`, `address3`, `institution`)
                          VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                    $this->trackingFormId,
                    mysql_escape_string($this->firstName),
                    mysql_escape_string($this->lastName),
                    mysql_escape_string($this->phone),
                    mysql_escape_string($this->email),
                    mysql_escape_string($this->address1),
                    mysql_escape_string($this->address2),
                    mysql_escape_string($this->address3),
                    mysql_escape_string($this->institution));
        } else {
            // we may want to create a table for external coresearchers later.  If so, write here
        }

        $result=$db->Execute($sql);

        if(!$result) {
            throw new Exception('Tracking form investigator cannot be saved.');
        }
    }

    public function remove() {
        global $db;

        if($this->isPI) {
            $sql = sprintf("DELETE FROM `forms_tracking_external_pi` WHERE `tracking_id` = %s", $this->trackingFormId);
        } else {
            // if we add external coresearchers later, then remove them here
        }

        $result=$db->Execute($sql);

        if(!$result) {
            throw new Exception('Tracking form investigator cannot be removed.');
        }
    }

    public function getDisplayName()
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    protected $trackingFormId;

    protected $isPI = false;

    protected $firstName;

    protected $lastName;

    protected $displayName;

    protected $phone;

    protected $email;

    protected $address1;

    protected $address2;

    protected $address3;

    protected $institution;

}
