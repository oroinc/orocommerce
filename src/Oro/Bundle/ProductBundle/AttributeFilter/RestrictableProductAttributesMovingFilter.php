<?php

namespace Oro\Bundle\ProductBundle\AttributeFilter;

use Oro\Bundle\EntityConfigBundle\AttributeFilter\AttributesMovingFilterInterface;

class RestrictableProductAttributesMovingFilter implements AttributesMovingFilterInterface
{
    const INVENTORY_STATUS_FIELD = 'inventory_status';

    const RESTRICTED_FIELDS = [
        self::INVENTORY_STATUS_FIELD,
    ];

    /** {@inheritdoc} */
    public function isRestrictedToMove($attributeName)
    {
        return in_array($attributeName, self::RESTRICTED_FIELDS);
    }
}
