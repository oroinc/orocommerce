<?php

namespace Oro\Bundle\ProductBundle\Visibility;

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
