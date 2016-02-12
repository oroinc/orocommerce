<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Resolver;

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
     * @return array
     */
    public function getVisibleCategoryIds();

    /**
     * @return array
     */
    public function getHiddenCategoryIds();

    /**
     * @param Category $category
     * @param AccountGroup $accountGroup
     * @return bool
     */
    public function isCategoryVisibleForAccountGroup(Category $category, AccountGroup $accountGroup);

    /**
     * @param AccountGroup $accountGroup
     * @return array
     */
    public function getVisibleCategoryIdsForAccountGroup(AccountGroup $accountGroup);

    /**
     * @param AccountGroup $accountGroup
     * @return array
     */
    public function getHiddenCategoryIdsForAccountGroup(AccountGroup $accountGroup);

    /**
     * @param Category $category
     * @param Account $account
     * @return bool
     */
    public function isCategoryVisibleForAccount(Category $category, Account $account);

    /**
     * @param Account $account
     * @return array
     */
    public function getVisibleCategoryIdsForAccount(Account $account);

    /**
     * @param Account $account
     * @return array
     */
    public function getHiddenCategoryIdsForAccount(Account $account);
}
