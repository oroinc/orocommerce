<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether product kit item line item quantity is in the range
 * specified in the product kit.
 */
class ProductKitItemLineItemQuantityRange extends Constraint
{
    public ?string $notInRangeMessage = null;
    public ?string $minMessage = null;
    public ?string $maxMessage = null;
}
