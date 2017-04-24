<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Result\Factory\Common\ParameterBag;

use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Factory;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\ParameterBag\ParameterBagShippingMethodValidatorResult;

class ParameterBagCommonShippingMethodValidatorResultFactory implements
    Factory\Common\CommonShippingMethodValidatorResultFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createSuccessResult()
    {
        return new ParameterBagShippingMethodValidatorResult(
            [
                ParameterBagShippingMethodValidatorResult::FIELD_ERRORS =>
                    new Error\Collection\Doctrine\DoctrineShippingMethodValidatorResultErrorCollection(),
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function createErrorResult(
        Error\Collection\ShippingMethodValidatorResultErrorCollectionInterface $errors
    ) {
        return new ParameterBagShippingMethodValidatorResult(
            [
                ParameterBagShippingMethodValidatorResult::FIELD_ERRORS => $errors,
            ]
        );
    }
}
