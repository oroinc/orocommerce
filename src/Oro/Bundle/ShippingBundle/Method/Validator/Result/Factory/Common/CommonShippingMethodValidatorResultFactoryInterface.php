<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Result\Factory\Common;

use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\ShippingMethodValidatorResultInterface;

/**
 * Defines the contract for factories that create shipping method validator results.
 *
 * Implementations of this interface provide methods to create {@see ShippingMethodValidatorResultInterface}
 * instances representing both successful validations and validation failures with error collections.
 */
interface CommonShippingMethodValidatorResultFactoryInterface
{
    /**
     * @return ShippingMethodValidatorResultInterface
     */
    public function createSuccessResult();

    /**
     * @param Error\Collection\ShippingMethodValidatorResultErrorCollectionInterface $errors
     *
     * @return ShippingMethodValidatorResultInterface
     */
    public function createErrorResult(
        Error\Collection\ShippingMethodValidatorResultErrorCollectionInterface $errors
    );
}
