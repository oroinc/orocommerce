<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Factory\Common\ParameterBag;

use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Factory;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\ParameterBag\ParameterBagShippingMethodValidatorResultError;

class ParameterBagCommonShippingMethodValidatorResultErrorFactory implements
    Factory\Common\CommonShippingMethodValidatorResultErrorFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createError($message)
    {
        return new ParameterBagShippingMethodValidatorResultError(
            [
                ParameterBagShippingMethodValidatorResultError::FIELD_MESSAGE => $message,
            ]
        );
    }
}
