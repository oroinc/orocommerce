<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class PositionChangeCategorySubtreeCacheBuilder extends VisibilityChangeCategorySubtreeCacheBuilder
{
    /** @var AccountGroup[] */
    protected $accountGroupsWithInverseVisibility = [];

    /** @var  Account[] */
    protected $accountsWithInverseVisibility = [];

    /**
     * @param Category $category
     */
    public function categoryPositionChanged(Category $category)
    {
        $this->clearChangedEntities();

        $visibility = $this->categoryVisibilityResolver->isCategoryVisible($category);
        $visibility = $this->convertVisibility($visibility);

        $categoryIds = $this->getCategoryIdsForUpdate($category, null);
        $this->updateProductVisibilityByCategory($categoryIds, $visibility);

        $this->updateAppropriateVisibilityRelatedEntities($category, $visibility);
        $this->updateInvertedVisibilityRelatedEntities($category, $visibility);
    }

    protected function clearChangedEntities()
    {
        parent::clearChangedEntities();

        $this->accountGroupsWithInverseVisibility = [];
        $this->accountsWithInverseVisibility = [];
    }

    /**
     * @param Category $category
     * @param int $visibility
     */
    protected function updateAppropriateVisibilityRelatedEntities(Category $category, $visibility)
    {
        $this->updateAccountGroupsAppropriateVisibility($category, $visibility);
        $this->updateAccountsAppropriateVisibility($category, $visibility);

        $this->updateProductVisibilitiesForCategoryRelatedEntities(
            $category,
            $visibility,
            $this->accountGroupsWithChangedVisibility[$category->getId()],
            $this->accountsWithChangedVisibility[$category->getId()]
        );
    }

    /**
     * @param Category $category
     * @param int $visibility
     */
    protected function updateAccountGroupsAppropriateVisibility(Category $category, $visibility)
    {
        $accountGroupsForUpdate = $this->getAccountGroupsFirstLevel($category);

        $accountGroupsWithFallbackToParent = $this->getCategoryAccountGroupsWithVisibilityFallbackToParent($category);

        $accountGroupsWithInverseVisibility = [];

        foreach ($accountGroupsWithFallbackToParent as $accountGroup) {
            $accountGroupVisibility = $this->categoryVisibilityResolver
                ->isCategoryVisibleForAccountGroup($category, $accountGroup);
            if ($accountGroupVisibility === $visibility) {
                $accountGroupsForUpdate[] = $accountGroup;
            } else {
                $accountGroupsWithInverseVisibility[] = $accountGroup;
            }
        }

        $this->updateAccountGroupsProductVisibility(
            $category,
            $accountGroupsForUpdate,
            $visibility
        );

        $this->accountGroupsWithChangedVisibility[$category->getId()] = $accountGroupsForUpdate;
        $this->accountGroupsWithInverseVisibility = $accountGroupsWithInverseVisibility;
    }

    /**
     * @param Category $category
     * @param int $visibility
     */
    protected function updateAccountsAppropriateVisibility(Category $category, $visibility)
    {
        $accountForUpdate = $this->getAccountsFirstLevel($category);
        $accountsWithFallbackToParent = $this->getAccountsWithFallbackToParent($category);

        $accountsWithInverseVisibility = [];
        foreach ($accountsWithFallbackToParent as $account) {
            $accountVisibility = $this->categoryVisibilityResolver->isCategoryVisibleForAccount($category, $account);
            if ($accountVisibility === $visibility) {
                $accountForUpdate[] = $account;
            } else {
                $accountsWithInverseVisibility[] = $account;
            }
        }

        $this->updateAccountsProductVisibility($category, $accountForUpdate, $visibility);

        $this->accountsWithChangedVisibility[$category->getId()] = $accountForUpdate;
        $this->accountsWithInverseVisibility = $accountsWithInverseVisibility;
    }

    /**
     * @param Category $category
     * @param int $visibility
     */
    protected function updateInvertedVisibilityRelatedEntities(Category $category, $visibility)
    {
        $invertedVisibility = $visibility * -1;

        $this->updateAccountGroupsProductVisibility(
            $category,
            $this->accountGroupsWithInverseVisibility,
            $invertedVisibility
        );

        $this->updateAccountsProductVisibility($category, $this->accountsWithInverseVisibility, $invertedVisibility);

        $this->updateProductVisibilitiesForCategoryRelatedEntities(
            $category,
            $invertedVisibility,
            $this->accountGroupsWithInverseVisibility,
            $this->accountsWithInverseVisibility
        );
    }
}
