<?php

namespace Oro\Bundle\ProductBundle\Visibility;

/**
 * Basic implementation of unit visibility that makes all units visible.
 *
 * This implementation provides the default behavior where all product unit codes are considered visible,
 * serving as the base visibility logic before any decorators or filters are applied.
 */
class BasicUnitVisibility implements UnitVisibilityInterface
{
    /**
     * @param string $code
     * @return bool
     */
    #[\Override]
    public function isUnitCodeVisible($code)
    {
        return true;
    }
}
