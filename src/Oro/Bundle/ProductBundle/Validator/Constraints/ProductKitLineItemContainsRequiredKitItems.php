<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for checking if a product kit line item contains all required kit item line items.
 */
class ProductKitLineItemContainsRequiredKitItems extends Constraint
{
    public const MISSING_REQUIRED_KIT_ITEM = 'f231acf1-3947-4ca2-8dbb-d7271f78c3ca';

    protected static $errorNames = [
        self::MISSING_REQUIRED_KIT_ITEM => 'MISSING_REQUIRED_KIT_ITEM',
    ];

    public string $message = 'oro.product.validators.product_kit_line_item_contains_required_kit_items'
        . '.missing_required_kit_item';

    #[\Override]
    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT];
    }
}
