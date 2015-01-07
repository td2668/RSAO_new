<?php
/**
 * Class to handle mailing via PEAR.
 *
 * Not to be confused with PEAR's Mail_Queue class.
 * This acts as a wrapper.
 */

require_once('includes/global.inc.php');
require_once "Mail/Queue.php";

class MailQueue
{

    /**
     * @param messages Email[] - an array of emails
     */
    public function __construct($emails) {
        global $configInfo;
        $this->mailQueue = new Mail_Queue($configInfo['email_db_options'], $configInfo['email_options']);
        $this->messages = $emails;
    }

    /**
     * Queue all the messages
     */
    public function queueAllMail() {
        foreach($this->messages as $email) {
            $result = $this->queueMail($email);
            if($result != $email->getSendNow()) {
                throw new Exception('Email was not delivered successfully');
            }
        }
    }

    /**
     * Add an email to the PEAR email queue
     *
     * @param $message - the message to add
     * @return bool $sendResult - whether the mail was sent successfully.
     */
    protected function queueMail($message) {
        global $db;
        global $configInfo;

        $send_result = false;

        $body = $message->getMimeBody();
        $hdrs = $message->getMimeHeader();

        $from = $message->getFromAddress();
        $recipient = $message->getRecipientAddress();
        $sendNow = $message->getSendNow();

        $queueMailId = $this->mailQueue->put($from, $recipient, $hdrs, $body);

        if ($sendNow) {
            $send_result = $this->mailQueue ->sendMailById( $queueMailId );
        }

        return $send_result;
    }

    /**
     * @var $messages[] - the messages to be sent
     */
    protected $messages;

    /**
     * @var string $fromEmail - the from email
     */
    protected $fromEmail = 'research@mtroyal.ca';

    /**
     * @var string $fromName - the from name
     */
    protected $fromName = 'Research Services';

    /**
     * @var Mail_Queue - the PEAR mail queue object
     */
    protected $mailQueue;
}
