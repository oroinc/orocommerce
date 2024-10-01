<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Result\ParameterBag;

use Oro\Bundle\ShippingBundle\Method\Validator\Result\Factory\Common;
use Oro\Bundle\ShippingBundle\Method\Validator\Result\ShippingMethodValidatorResultInterface;

/**
 * DTO for shipping method validation result
 */
class ParameterBagShippingMethodValidatorResult implements ShippingMethodValidatorResultInterface
{
    public function __construct(
        protected array $errors = []
    ) {
    }

    #[\Override]
    public function createCommonFactory()
    {
        return new Common\ParameterBag\ParameterBagCommonShippingMethodValidatorResultFactory();
    }

    #[\Override]
    public function getErrors()
    {
        return $this->errors;
    }
}
