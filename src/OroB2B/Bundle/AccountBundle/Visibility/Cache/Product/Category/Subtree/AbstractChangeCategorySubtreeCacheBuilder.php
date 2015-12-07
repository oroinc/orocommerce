<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree;

use OroB2B\Bundle\CatalogBundle\Entity\Category;

abstract class AbstractChangeCategorySubtreeCacheBuilder extends AbstractSubtreeCacheBuilder
{
    /**
     * @param Category $category
     * @param $visibility
     * @return mixed
     */
    abstract protected function updateAccountGroupsFirstLevel(Category $category, $visibility);

    /**
     * @param Category $category
     * @param $visibility
     * @return mixed
     */
    abstract protected function updateAccountsFirstLevel(Category $category, $visibility);
}
