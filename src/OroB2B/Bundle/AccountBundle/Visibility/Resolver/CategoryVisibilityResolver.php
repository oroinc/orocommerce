<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Resolver;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Visibility\Storage\CategoryVisibilityStorage;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryVisibilityResolver implements CategoryVisibilityResolverInterface
{
    /**
     * @var CategoryVisibilityStorage
     */
    protected $storage;

    /**
     * @param CategoryVisibilityStorage $storage
     */
    public function __construct(CategoryVisibilityStorage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * {@inheritdoc}
     */
    public function isCategoryVisible(Category $category)
    {
        return $this->storage->getCategoryVisibilityData()->isCategoryVisible($category->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibleCategoryIds()
    {
        return $this->storage->getCategoryVisibilityData()->getVisibleCategoryIds();
    }

    /**
     * {@inheritdoc}
     */
    public function getHiddenCategoryIds()
    {
        return $this->storage->getCategoryVisibilityData()->getHiddenCategoryIds();
    }

    /**
     * @param Category $category
     * @param AccountGroup $accountGroup
     * @return bool
     */
    public function isCategoryVisibleForAccountGroup(Category $category, AccountGroup $accountGroup)
    {
        return $this->storage->getCategoryVisibilityDataForAccountGroup($accountGroup)
            ->isCategoryVisible($category->getId());
    }

    /**
     * @param AccountGroup $accountGroup
     * @return array
     */
    public function getVisibleCategoryIdsForAccountGroup(AccountGroup $accountGroup)
    {
        return $this->storage->getCategoryVisibilityDataForAccountGroup($accountGroup)->getVisibleCategoryIds();
    }

    /**
     * @param AccountGroup $accountGroup
     * @return array
     */
    public function getHiddenCategoryIdsForAccountGroup(AccountGroup $accountGroup)
    {
        return $this->storage->getCategoryVisibilityDataForAccountGroup($accountGroup)->getHiddenCategoryIds();
    }

    /**
     * @param Category $category
     * @param Account|null $account
     * @return bool
     */
    public function isCategoryVisibleForAccount(Category $category, Account $account = null)
    {
        return $this->storage->getCategoryVisibilityDataForAccount($account)->isCategoryVisible($category->getId());
    }

    /**
     * @param Account $account
     * @return array
     */
    public function getVisibleCategoryIdsForAccount(Account $account)
    {
        return $this->storage->getCategoryVisibilityDataForAccount($account)->getVisibleCategoryIds();
    }

    /**
     * @param Account $account
     * @return array
     */
    public function getHiddenCategoryIdsForAccount(Account $account)
    {
        return $this->storage->getCategoryVisibilityDataForAccount($account)->getHiddenCategoryIds();
    }
}
