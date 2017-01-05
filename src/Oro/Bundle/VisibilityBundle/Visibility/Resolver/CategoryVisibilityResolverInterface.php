<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Resolver;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CatalogBundle\Entity\Category;

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
     * @param CustomerGroup $accountGroup
     * @return bool
     */
    public function isCategoryVisibleForAccountGroup(Category $category, CustomerGroup $accountGroup);

    /**
     * @param CustomerGroup $accountGroup
     * @return array
     */
    public function getVisibleCategoryIdsForAccountGroup(CustomerGroup $accountGroup);

    /**
     * @param CustomerGroup $accountGroup
     * @return array
     */
    public function getHiddenCategoryIdsForAccountGroup(CustomerGroup $accountGroup);

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
