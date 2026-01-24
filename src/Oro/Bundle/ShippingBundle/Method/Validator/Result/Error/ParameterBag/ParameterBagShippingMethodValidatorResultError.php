<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\ParameterBag;

use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\ShippingMethodValidatorResultErrorInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Parameter bag-based container for shipping method validation error information.
 *
 * This class stores validation error messages using a parameter bag structure, providing
 * a flexible way to represent validation failures in shipping method operations.
 */
class ParameterBagShippingMethodValidatorResultError extends ParameterBag implements
    ShippingMethodValidatorResultErrorInterface
{
    const FIELD_MESSAGE = 'message';

    #[\Override]
    public function getMessage()
    {
        return $this->get(self::FIELD_MESSAGE);
    }
}
