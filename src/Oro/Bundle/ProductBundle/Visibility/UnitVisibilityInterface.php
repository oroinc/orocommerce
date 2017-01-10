<?php

namespace Oro\Bundle\ProductBundle\Visibility;

interface UnitVisibilityInterface
{
    /**
     * @param string $code
     * @return bool
     */
    public function isUnitCodeVisible($code);
}
