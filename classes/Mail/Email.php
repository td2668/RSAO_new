<?php
/*
 * An email object
 */

require_once('includes/global.inc.php');

class Email {

    /**
     * The constructor
     *
     * @param $recipientAddress - the recipients email address
     * @param $recipientName - the recipients name
     * @param $subject - the subject of the mail
     * @param $body - the email body (plain-text)
     *
     * @throws Exception - on invalid email address
     */
    public function __construct($recipientAddress, $recipientName, $subject, $body) {
        global $configInfo;

        if(!isset($configInfo["debug_email"])) {
            $configInfo["debug_email"] = false;
        }

        if($configInfo["debug_email"] ) {
            $recipientAddress = $configInfo["debug_email"];
            $recipientName = $configInfo["debug_email_name"];
        }

        if($this->isValidEmail($recipientAddress)) {
            $this->recipientAddress = $recipientAddress;
        } else {
            throw new Exception('Invalid email address supplied: ' . $recipientAddress);
        }

        $this->recipientName = $recipientName;
        $this->subject = $subject;
        $this->body = $body;

        if($configInfo["email_send_now"]){
            $this->setSendNow();
        }

        $this->mime = new Mail_mime();
    }

    /**
     * @return string - the recipient address
     */
    public function getRecipientAddress() {
            return $this->recipientAddress;
    }

    /**
     * @return string - the recipient's name
     */
    public function getRecipientName() {
        return $this->recipientName;
    }

    /**
     * @return string - the subject
     */
    public function getSubject() {
        return $this->subject;
    }

    /**
     * @return string - the sender's address
     */
    public function getFromAddress() {
        return $this->fromAddress;
    }

    /**
     * Get the sendNow status
     *
     * @return bool - whether to send the message immediately.
     */
    public function getSendNow() {
        return $this->sendNow;
    }

    /**
     * Set the flag to send the email immediately
     */
    public function setSendNow() {
        $this->sendNow = true;
    }

    /**
     * Get the MIME body
     *
     * @return mixed - the MIME body
     */
    public function getMimeBody() {
        $body = $this->body;
        $this->mime->setTXTBody($body);

        return $this->mime->get();
    }

    /**
     * Get the MIME header
     *
     * @return mixed - the MIME header
     */
    public function getMimeHeader() {
        $from_params = empty( $this->fromName ) ? '<' . $this->fromAddress . '>' : '"' . $this->fromName . '" <' . $this->fromAddress . '>';
        $recipient_params = empty( $recipient_name ) ? '<' . $this->recipientAddress . '>' : '"' . $this->recipientName . '" <' . $this->recipientAddress . '>';

        $hdrs = array(
            'From' => $from_params,
            'To' => $recipient_params,
            'Subject' => $this->subject
        );

        return $this->mime->headers($hdrs);
    }

    /**
     * Validate an email address
     *
     * @param $email - the email address to validate
     * @return int - greater than 0 if valid
     */
    protected function isValidEmail($email){
        return preg_match("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$^", $email);
    }

    protected $mime;

    protected $fromAddress = 'research@mtroyal.ca';

    protected $fromName = 'Research Services';

    protected $recipientAddress;

    protected $recipientName;

    protected $subject;

    protected $body;

    protected $sendNow = false;
}

