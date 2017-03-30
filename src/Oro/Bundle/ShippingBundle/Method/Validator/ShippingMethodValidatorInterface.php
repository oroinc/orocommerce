<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\ShippingMethodValidatorResultInterface;

interface ShippingMethodValidatorInterface
{
    /**
     * @param ShippingMethodInterface $shippingMethod
     *
     * @return ShippingMethodValidatorResultInterface
     */
    public function validate(ShippingMethodInterface $shippingMethod);
}
