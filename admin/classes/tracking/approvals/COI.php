<?php

namespace tracking\approval;

use tracking\Approval;

require_once('classes/tracking/Approval.php');

/**
 * Defines a approval of type COI - Conflict of Interest
 */
class COI extends Approval 
{
    /**
     * Constructor
     */
    function __construct($trackingFormId)
    {
        $this->trackingFormId = $trackingFormId;
        $this->type = COI;
        $this->name = $this->getFriendlyName();
        $this->status = PRESUBMITTED;
    }

    /**
     *  Get email subject - Implemented in inherited class
     *
     * @param $trackingForm
     * @return string - the email subject
     */
    protected function getEmailSubject($trackingForm)
    {
        $subject = sprintf("[TID-%s] - Tracking form approved by Office of Research Services", $trackingForm->trackingFormId);

        return $subject;
    }

    /**
     * Get email body - Implemented in inherited class
     *
     * @param $trackingForm
     * @return string - the email body
     */
    protected function getEmailBody($trackingForm)
    {
        $body = sprintf("Hello %s,

The Tracking Form (TID-%s) have been approved by the Office of Research Services.  If ethics are required, then the Human Research Ethics board may still required approval before full approval is awarded.

Regards,
Office of Research Services
", $trackingForm->submitter->firstName , $trackingForm->trackingFormId);

        return $body;
    }
}
