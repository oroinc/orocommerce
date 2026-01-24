<?php

namespace Oro\Bundle\ProductBundle\Visibility;

/**
 * Defines the contract for determining product unit code visibility.
 *
 * Implementations of this interface provide logic to determine whether a specific product unit code should be visible
 * in the user interface based on configuration, single unit mode settings, or other business rules.
 */
interface UnitVisibilityInterface
{
    /**
     * @param string $code
     * @return bool
     */
    public function isUnitCodeVisible($code);
}
