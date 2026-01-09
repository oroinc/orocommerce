<?php

namespace Oro\Bundle\ProductBundle\Service;

/**
 * Defines the contract for managing single unit mode functionality.
 *
 * Implementations of this interface provide methods to check if single unit mode is enabled,
 * whether the unit code should be visible in that mode, and to retrieve the default unit code
 * used when single unit mode is active.
 */
interface SingleUnitModeServiceInterface
{
    /**
     * @return bool
     */
    public function isSingleUnitMode();

    /**
     * @return bool
     */
    public function isSingleUnitModeCodeVisible();

    /**
     * @return string
     */
    public function getDefaultUnitCode();
}
