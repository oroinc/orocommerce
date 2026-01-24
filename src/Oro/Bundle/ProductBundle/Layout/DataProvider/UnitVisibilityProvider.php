<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Visibility\UnitVisibilityInterface;

/**
 * Provides product unit visibility information for layout rendering.
 *
 * This data provider exposes unit visibility logic to layout templates, allowing templates
 * to determine whether specific product unit codes should be displayed based on system configuration
 * and single unit mode settings.
 */
class UnitVisibilityProvider
{
    /**
     * @var UnitVisibilityInterface
     */
    private $unitVisibility;

    public function __construct(UnitVisibilityInterface $unitVisibility)
    {
        $this->unitVisibility = $unitVisibility;
    }

    /**
     * @param $code
     * @return bool
     */
    public function isUnitCodeVisible($code)
    {
        return $this->unitVisibility->isUnitCodeVisible($code);
    }
}
