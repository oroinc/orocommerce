<?php

namespace Oro\Bundle\CatalogBundle\Visibility;

interface CategoryDefaultProductUnitOptionsVisibilityInterface
{
    /**
     * @return bool
     */
    public function isDefaultUnitPrecisionSelectionAvailable();
}
