<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Result\Factory\Common\ParameterBag;

use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\Factory;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\ParameterBag\ParameterBagShippingMethodValidatorResult;

/**
 * Creates parameter bag-based shipping method validator results.
 *
 * This factory produces {@see ParameterBagShippingMethodValidatorResult} instances representing
 * both successful and failed validation outcomes for shipping methods.
 */
class ParameterBagCommonShippingMethodValidatorResultFactory implements
    Factory\Common\CommonShippingMethodValidatorResultFactoryInterface
{
    #[\Override]
    public function createSuccessResult()
    {
        return new ParameterBagShippingMethodValidatorResult(
            [
                ParameterBagShippingMethodValidatorResult::FIELD_ERRORS =>
                    new Error\Collection\Doctrine\DoctrineShippingMethodValidatorResultErrorCollection(),
            ]
        );
    }

    #[\Override]
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
