<?php

namespace Oro\Bundle\RFPBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class FormSubmitCheckEvent extends Event
{
    const NAME = 'rfp.form_submit_check';

    /**
     * @var bool
     */
    protected $submitOnError = false;

    /**
     * @return bool
     */
    public function isSubmitOnError()
    {
        return $this->submitOnError;
    }

    /**
     * @param bool $submitOnError
     * @return $this
     */
    public function setShouldSubmitOnError($submitOnError)
    {
        $this->submitOnError = $submitOnError;

        return $this;
    }
}
