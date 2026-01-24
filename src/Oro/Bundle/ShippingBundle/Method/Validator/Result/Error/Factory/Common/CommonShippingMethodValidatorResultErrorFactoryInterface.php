<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Factory\Common;

use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\ShippingMethodValidatorResultErrorInterface;

/**
 * Defines the contract for factories that create shipping method validator result errors.
 *
 * Implementations of this interface provide a way to create {@see ShippingMethodValidatorResultErrorInterface}
 * instances from error messages, encapsulating validation failure information.
 */
interface CommonShippingMethodValidatorResultErrorFactoryInterface
{
    /**
     * @param string $message
     *
     * @return ShippingMethodValidatorResultErrorInterface
     */
    public function createError($message);
}
