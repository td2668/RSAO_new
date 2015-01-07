<?php
/**
 * DeanReview - isn't an approval, as such, but rather a flag so that the Dean can review the form
 */
namespace tracking\approval;

use tracking\Approval;

require_once('classes/tracking/Approval.php');

/*
 * Defines an approval of type DeanReview
 */
class DeanReview extends Approval
{
    /**
     * Constructor
     */
    function __construct($trackingFormId)
    {
        $this->trackingFormId = $trackingFormId;
        $this->type = DEAN_REVIEW;
        $this->name = $this->getFriendlyName();
        $this->status = PRESUBMITTED;
    }

    /**
     *  Get email subject - Implemented in inherited class
     *
     * @param $trackingForm
     * @return string - the email subject
     */
    public function getEmailSubject($trackingForm)
    {
        $subject = sprintf("[TID-%s] - Tracking form reviewed by Dean", $trackingForm->trackingFormId);

        return $subject;
    }

    /**
     * Get email body - Implemented in inherited class
     *
     * @param $trackingForm
     * @return string - the email body
     */
    public function getEmailBody($trackingForm)
    {
        $body = sprintf("Hello %s,

The Tracking Form (TID-%s) have been reviewed by your Dean.  Other approvals or reviews may still be required by the Office of Research Services and/or Human Research Ethics before full approval is awarded.

Comments: %s

Regards,
Office of Research Services
", $trackingForm->submitter->firstName , $trackingForm->trackingFormId, $this->getPlainTextComments());

        return $body;

    }

}