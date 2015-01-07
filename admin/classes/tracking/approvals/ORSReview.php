<?php
/**
 * DeanReview - isn't an approval, as such, but rather a flag so that the ORS can review the form if there are no COI declared
 */
namespace tracking\approval;

use tracking\Approval;

require_once('classes/tracking/Approval.php');

/*
 * Defines an approval of type ORSReview
 */
class ORSReview extends Approval
{
    /**
     * Constructor
     */
    function __construct($trackingFormId)
    {
        $this->trackingFormId = $trackingFormId;
        $this->type = ORS_REVIEW;
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
        $subject = sprintf("[TID-%s] - Tracking form reviewed by ORS", $trackingForm->trackingFormId);

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

The Tracking Form (TID-%s) have been reviewed by the Office of Research Services.  Other approvals or reviews may still be required by your Dean and/or Human Research Ethics before full approval is awarded.

Regards,
Office of Research Services
", $trackingForm->submitter->firstName , $trackingForm->trackingFormId);

        return $body;

    }

}