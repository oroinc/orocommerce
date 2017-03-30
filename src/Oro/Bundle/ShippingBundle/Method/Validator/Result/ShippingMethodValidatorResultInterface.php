<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Result;

use Oro\Bundle\ShippingBundle\Method\Validator\Result;

interface ShippingMethodValidatorResultInterface
{
    /**
     * @return Result\Factory\Common\CommonShippingMethodValidatorResultFactoryInterface
     */
    public function createCommonFactory();

    /**
     * @return Result\Error\Collection\ShippingMethodValidatorResultErrorCollectionInterface
     */
    public function getErrors();
}
