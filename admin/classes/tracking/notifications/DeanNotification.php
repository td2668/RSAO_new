<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ischuyt
 * Date: 03/07/13
 * Time: 10:59 AM
 */

namespace tracking\notifications;

require_once('includes/global.inc.php');

class DeanNotification extends Notifications {


    function __construct($subject, $body, $trackingForm)
    {
        $deanContact = $this->getDeanContactDetails($trackingForm);

        $this->recipients = $deanContact;
        $this->subject = $subject;
        $this->body = $body;
    }

    /**
     * Get the Dean's email address
     */
    private function getDeanContactDetails($trackingForm) {
        global $db;

        $sql = "SELECT profiles.email, CONCAT(users.first_name, ' ', users.last_name) AS name, divisions.division_id FROM `divisions`
                LEFT JOIN departments ON departments.division_id = divisions.division_id
                LEFT JOIN profiles ON divisions.dean = profiles.user_id
                LEFT JOIN users ON users.user_id = divisions.dean
                WHERE departments.department_id = (SELECT users.department_id FROM users WHERE user_id = " . $trackingForm->submitter->getUserId() . ")";
        $result = $db->getRow($sql);

        /**
         * We have an override here so Vince Salyers receives notifications for Health
         */
        if($result['division_id'] == 18) {
            return(array('vsalyers@mtroyal.ca' => 'vsalyers@mtroyal.ca'));
        }

        if(!$result || $result['name'] == NULL || $result['email'] == "") {
            throw new Exception('Unable to determine contact details to notify Dean');
        }

        return(array($result['email'] =>  $result['email']));
    }

}