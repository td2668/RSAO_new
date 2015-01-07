<?php
/**
 * Class to handle notifications initiated by tracking form activities
 * User: ischuyt
 * Date: 03/07/13
 * Time: 10:52 AM
 */

namespace tracking\notifications;

use MailQueue;
use Email;
use Exception;

class Notifications {


    function __construct()
    {
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

    public function send() {
        require_once('classes/Mail/MailQueue.php');
        require_once('classes/Mail/Email.php');

        // don't send the email if either subject or body are empty
        if(strlen($this->subject) == 0 ||  strlen($this->body) == 0) {
            throw new Exception("Empty subject or body, unable to send notification");
        }

        $emails = array();
        foreach($this->recipients AS $recipientName=>$recipientAddress) {
            $emails[] = new Email(
                $recipientAddress,
                $recipientName,
                $this->subject,
                $this->body
            );
        }

        $mailQueue = new MailQueue($emails);
        $mailQueue->queueAllMail();
    }


    /**
     * @var array('recipientName' => 'recpient@domain.com', ...)
     */
    protected $recipients;

    /**
     * @var string - the email subject
     */
    protected $subject;

    /**
     * @var - string - the plain-text body
     */
    protected $body;

}