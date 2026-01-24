<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\ShippingMethodValidatorResultInterface;

/**
 * Defines the contract for shipping method validators.
 *
 * Implementations of this interface validate shipping methods to ensure they can be safely modified or deleted,
 * checking for active usage in configurations and rules.
 */
interface ShippingMethodValidatorInterface
{
    /**
     * @param ShippingMethodInterface $shippingMethod
     *
     * @return ShippingMethodValidatorResultInterface
     */
    public function validate(ShippingMethodInterface $shippingMethod);
}
