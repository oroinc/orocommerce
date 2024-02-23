<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for checking if the quantity specified in a kit item line item
 *  follows the allowed precision.
 */
class ProductKitItemLineItemQuantityUnitPrecision extends Constraint
{
    public const INVALID_PRECISION = 'a75b1dee-be74-4f88-9bb7-d74d5e16a031';

    protected static $errorNames = [self::INVALID_PRECISION => 'INVALID_PRECISION'];

    public string $message = 'oro.product.productkititemlineitem.quantity.invalid_precision.message';

    public string $unitPrecisionPropertyPath = '';
}
