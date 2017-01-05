<?php

namespace Oro\Bundle\CatalogBundle\Visibility;

class BasicCategoryDefaultProductOptionsVisibility implements CategoryDefaultProductOptionsVisibilityInterface
{
    /**
     * {@inheritdoc}
     */
    public function isDefaultUnitPrecisionSelectionAvailable()
    {
        return true;
    }
}
