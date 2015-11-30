<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Calculator;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryVisibilityResolver
{
    /**
     * @param Category $category
     * @return bool
     */
    public function isCategoryVisible(Category $category)
    {
        // TODO: Implement isCategoryVisible() method.
    }

    /**
     * @param Category $category
     * @param AccountGroup $accountGroup
     * @return bool
     */
    public function isCategoryVisibleForAccountGroup(Category $category, AccountGroup $accountGroup)
    {
        // TODO: Implement isCategoryVisibleForAccountGroup() method.
    }

    /**
     * @param Category $category
     * @param Account $account
     * @return bool
     */
    public function isCategoryVisibleForAccount(Category $category, Account $account)
    {
        // TODO: Implement isCategoryVisibleForAccount() method.
    }
}
