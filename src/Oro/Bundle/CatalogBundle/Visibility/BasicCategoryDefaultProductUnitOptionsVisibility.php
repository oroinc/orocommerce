<?php

namespace Oro\Bundle\CatalogBundle\Visibility;

class BasicCategoryDefaultProductUnitOptionsVisibility implements CategoryDefaultProductUnitOptionsVisibilityInterface
{
    /**
     * {@inheritdoc}
     */
    public function isDefaultUnitPrecisionSelectionAvailable()
    {
        return true;
    }
}
