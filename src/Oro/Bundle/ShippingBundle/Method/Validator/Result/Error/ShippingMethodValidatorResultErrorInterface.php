<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Result\Error;

/**
 * Defines the contract for shipping method validation error objects.
 *
 * Implementations of this interface provide access to validation error messages that describe
 * why a shipping method validation failed.
 */
interface ShippingMethodValidatorResultErrorInterface
{
    /**
     * @return string
     */
    public function getMessage();
}
