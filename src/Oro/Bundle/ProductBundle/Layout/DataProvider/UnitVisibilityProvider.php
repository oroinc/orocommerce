<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Visibility\UnitVisibilityInterface;

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
