<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Factory\Common\ParameterBag;

use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Factory;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\ParameterBag\ParameterBagShippingMethodValidatorResultError;

/**
 * Creates parameter bag-based shipping method validator result errors.
 *
 * This factory produces {@see ParameterBagShippingMethodValidatorResultError} instances
 * containing validation error messages for shipping method validation failures.
 */
class ParameterBagCommonShippingMethodValidatorResultErrorFactory implements
    Factory\Common\CommonShippingMethodValidatorResultErrorFactoryInterface
{
    #[\Override]
    public function createError($message)
    {
        return new ParameterBagShippingMethodValidatorResultError(
            [
                ParameterBagShippingMethodValidatorResultError::FIELD_MESSAGE => $message,
            ]
        );
    }
}
