<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Calculator;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryVisibilityResolver implements CategoryVisibilityResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function isCategoryVisible(Category $category)
    {
        // TODO: Implement isCategoryVisible() method.
    }

    /**
     * {@inheritdoc}
     */
    public function isCategoryVisibleForAccountGroup(Category $category, AccountGroup $accountGroup)
    {
        // TODO: Implement isCategoryVisibleForAccountGroup() method.
    }

    /**
     * {@inheritdoc}
     */
    public function isCategoryVisibleForAccount(Category $category, Account $account)
    {
        // TODO: Implement isCategoryVisibleForAccount() method.
    }
}
