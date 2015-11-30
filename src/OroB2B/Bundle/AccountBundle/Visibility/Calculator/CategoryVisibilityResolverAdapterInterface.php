<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Calculator;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

interface CategoryVisibilityResolverAdapterInterface
{
    /**
     * @param Category $category
     * @return integer
     */
    public function getCategoryVisibility(Category $category);

    /**
     * @param Category $category
     * @param AccountGroup $accountGroup
     * @return integer
     */
    public function getCategoryVisibilityForAccountGroup(Category $category, AccountGroup $accountGroup);

    /**
     * @param Category $category
     * @param Account $account
     * @return integer
     */
    public function getCategoryVisibilityForAccount(Category $category, Account $account);
}