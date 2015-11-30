<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Calculator;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

interface CategoryVisibilityResolverInterface
{
    /**
     * @param Category $category
     * @return bool
     */
    public function isCategoryVisible(Category $category);

    /**
     * @param Category $category
     * @param AccountGroup $accountGroup
     * @return bool
     */
    public function isCategoryVisibleForAccountGroup(Category $category, AccountGroup $accountGroup);

    /**
     * @param Category $category
     * @param Account $account
     * @return bool
     */
    public function isCategoryVisibleForAccount(Category $category, Account $account);
}