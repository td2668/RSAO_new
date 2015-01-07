<?php

namespace tracking\approval;

use tracking\Approval;

require_once('classes/tracking/Approval.php');

/*
 * Defines an approval of type commitments
 */
class Commitments extends Approval
{
    /**
     * Constructor
     */
    function __construct($trackingFormId)
    {
        $this->trackingFormId = $trackingFormId;
        $this->type = COMMITMENTS;
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
        $subject = sprintf("[TID-%s] - Tracking form approved by Dean", $trackingForm->trackingFormId);

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

The commitments associated with Tracking Form (TID-%s) have been approved by your Dean.  Other approvals or reviews may still be required by the Office of Research Services and/or Human Research Ethics before full approval is awarded.

Comments: %s

Regards,
Office of Research Services
", $trackingForm->submitter->firstName, $trackingForm->trackingFormId, $this->getPlainTextComments());

        return $body;

    }
}
