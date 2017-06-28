<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Result\Factory\Common;

use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\ShippingMethodValidatorResultInterface;

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
