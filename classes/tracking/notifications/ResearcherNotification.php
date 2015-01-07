<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ischuyt
 * Date: 03/07/13
 * Time: 10:59 AM
 */

namespace tracking\notifications;


class ResearcherNotification extends Notifications {


    /**
     * @param $subject - the email subject
     * @param $body - the email body
     * @param $investigator - tracking/Investigator
     */
    function __construct($subject, $body, $investigator)
    {
        $this->recipients = array($investigator->firstName . " " . $investigator->lastName => $investigator->email);
        $this->subject = $subject;
        $this->body = $body;
    }

}