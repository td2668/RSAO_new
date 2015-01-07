<?php

namespace tracking\approval;

use tracking\Approval;

require_once('classes/tracking/Approval.php');

/**
 * Defines a approval of type EthicsBehavioural
 */
class EthicsBehavioural extends Approval
{

    /**
     * Constructor
     */
    function __construct($trackingFormId)
    {
        $this->trackingFormId = $trackingFormId;
        $this->type = ETHICS_BEHAVIOURAL;
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
        // TODO: Implement getEmailSubject() method.
    }

    /**
     * Get email body - Implemented in inherited class
     *
     * @param $trackingForm
     * @return string - the email body
     */
    protected function getEmailBody($trackingForm)
    {
        // TODO: Implement getEmailBody() method.
    }
}
