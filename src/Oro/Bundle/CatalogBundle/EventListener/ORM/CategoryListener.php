<?php

namespace Oro\Bundle\CatalogBundle\EventListener\ORM;

use Oro\Bundle\CatalogBundle\Entity\Category;

class CategoryListener
{
    /**
     * @param Category $category
     */
    public function calculateMaterializedPath(Category $category)
    {
        $path = (string) $category->getId();
        $parent = $category->getParentCategory();
        while ($parent) {
            $path = $parent->getId().Category::MATERIALIZED_PATH_DELIMITER.$path;
            $parent = $parent->getParentCategory();
        }

        $category->setMaterializedPath($path);
    }
}
