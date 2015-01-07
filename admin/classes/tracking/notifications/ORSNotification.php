<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ischuyt
 * Date: 03/07/13
 * Time: 10:59 AM
 */

namespace tracking\notifications;


class ORSNotification extends Notifications {


    function __construct($subject, $body)
    {
        $this->recipients = array( 'Jerri-Lynne Cameron' => 'jcameron@mtroyal.ca');
        $this->subject = $subject;
        $this->body = $body;
    }

}