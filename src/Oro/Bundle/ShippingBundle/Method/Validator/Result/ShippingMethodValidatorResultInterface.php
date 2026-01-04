<?php

namespace Oro\Bundle\ShippingBundle\Method\Validator\Result;

use Oro\Bundle\ShippingBundle\Method\Validator\Result;

/**
 * DTO for shipping method validation result
 */
interface ShippingMethodValidatorResultInterface
{
    public const FIELD_ERRORS = 'errors';

    /**
     * @return Result\Factory\Common\CommonShippingMethodValidatorResultFactoryInterface
     */
    public function createCommonFactory();

    /**
     * @return Result\Error\Collection\ShippingMethodValidatorResultErrorCollectionInterface
     */
    public function getErrors();
}
