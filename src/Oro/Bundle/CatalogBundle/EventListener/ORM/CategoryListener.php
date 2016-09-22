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
        $recursiveCompletePath = function (Category $category) use (&$path, &$recursiveCompletePath) {
            $parent = $category->getParentCategory();
            if ($parent) {
                $path = $parent->getId().Category::MATERIALIZED_PATH_DELIMITER.$path;
                $recursiveCompletePath($parent);
            }
        };

        $recursiveCompletePath($category);
        $category->setMaterializedPath($path);
    }
}
