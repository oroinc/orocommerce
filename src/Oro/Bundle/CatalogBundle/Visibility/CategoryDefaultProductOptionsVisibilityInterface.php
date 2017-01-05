<?php

namespace Oro\Bundle\CatalogBundle\Visibility;

interface CategoryDefaultProductOptionsVisibilityInterface
{
    /**
     * @return bool
     */
    public function isDefaultUnitPrecisionSelectionAvailable();
}
