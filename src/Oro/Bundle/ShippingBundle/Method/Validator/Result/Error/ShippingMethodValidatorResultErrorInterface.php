<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Result\Error;

interface ShippingMethodValidatorResultErrorInterface
{
    /**
     * @return string
     */
    public function getMessage();
}
