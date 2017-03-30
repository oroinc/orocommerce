<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\Factory\Common;

use Oro\Bundle\ShippingBundle\Method\Validator\Result\Error\ShippingMethodValidatorResultErrorInterface;

interface CommonShippingMethodValidatorResultErrorFactoryInterface
{
    /**
     * @param string $message
     *
     * @return ShippingMethodValidatorResultErrorInterface
     */
    public function createError($message);
}
