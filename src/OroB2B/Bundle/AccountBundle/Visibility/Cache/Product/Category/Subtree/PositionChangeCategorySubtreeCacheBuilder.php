<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree;

use OroB2B\Bundle\CatalogBundle\Entity\Category;

class PositionChangeCategorySubtreeCacheBuilder extends VisibilityChangeCategorySubtreeCacheBuilder
{
    /** @var array */
    protected $accountGroupIdsWithInverseVisibility = [];

    /** @var array */
    protected $accountIdsWithInverseVisibility = [];

    /**
     * @param Category $category
     */
    public function categoryPositionChanged(Category $category)
    {
        $visibility = $this->categoryVisibilityResolver->isCategoryVisible($category);
        $visibility = $this->convertVisibility($visibility);

        $childCategoryIds = $this->getChildCategoryIdsForUpdate($category);
        $categoryIds = $this->getCategoryIdsForUpdate($category, $childCategoryIds);
        $this->updateProductVisibilityByCategory($categoryIds, $visibility);

        $this->updateAppropriateVisibilityRelatedEntities($category, $visibility);
        $this->updateInvertedVisibilityRelatedEntities($category, $visibility);

        $this->clearChangedEntities();
    }

    protected function clearChangedEntities()
    {
        parent::clearChangedEntities();

        $this->accountGroupIdsWithInverseVisibility = [];
        $this->accountIdsWithInverseVisibility = [];
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
            $this->accountGroupIdsWithChangedVisibility[$category->getId()],
            $this->accountIdsWithChangedVisibility[$category->getId()]
        );
    }

    /**
     * @param Category $category
     * @param int $visibility
     */
    protected function updateAccountGroupsAppropriateVisibility(Category $category, $visibility)
    {
        $accountGroupIdsForUpdate = $this->getAccountGroupIdsFirstLevel($category);

        $accountGroupIdsWithFallbackToParent = $this
            ->getCategoryAccountGroupIdsWithVisibilityFallbackToParent($category);

        $accountGroupIdsWithInverseVisibility = [];

        $accountGroupsVisibilities = $this->categoryVisibilityResolver
            ->getCategoryVisibilitiesForAccountGroups($category, $accountGroupIdsWithFallbackToParent);

        foreach ($accountGroupsVisibilities as $accountGroupId => $accountGroupVisibility) {
            if ($accountGroupVisibility === $visibility) {
                $accountGroupIdsForUpdate[] = $accountGroupId;
            } else {
                $accountGroupIdsWithInverseVisibility[] = $accountGroupId;
            }
        }

        $this->updateAccountGroupsProductVisibility(
            $category,
            $accountGroupIdsForUpdate,
            $visibility
        );

        $this->accountGroupIdsWithChangedVisibility[$category->getId()] = $accountGroupIdsForUpdate;
        $this->accountGroupIdsWithInverseVisibility = $accountGroupIdsWithInverseVisibility;
    }

    /**
     * @param Category $category
     * @param int $visibility
     */
    protected function updateAccountsAppropriateVisibility(Category $category, $visibility)
    {
        $accountIdsForUpdate = $this->getAccountIdsFirstLevel($category);
        $accountIdsWithFallbackToParent = $this->getAccountIdsWithFallbackToParent($category);

        $accountIdsWithInverseVisibility = [];

        $accountsVisibilities = $this->categoryVisibilityResolver
            ->getCategoryVisibilitiesForAccounts($category, $accountIdsWithFallbackToParent);

        foreach ($accountsVisibilities as $accountId => $accountVisibility) {
            if ($accountVisibility === $visibility) {
                $accountIdsForUpdate[] = $accountId;
            } else {
                $accountIdsWithInverseVisibility[] = $accountId;
            }
        }

        $this->updateAccountsProductVisibility($category, $accountIdsForUpdate, $visibility);

        $this->accountIdsWithChangedVisibility[$category->getId()] = $accountIdsForUpdate;
        $this->accountIdsWithInverseVisibility = $accountIdsWithInverseVisibility;
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
            $this->accountGroupIdsWithInverseVisibility,
            $invertedVisibility
        );

        $this->updateAccountsProductVisibility($category, $this->accountIdsWithInverseVisibility, $invertedVisibility);

        $this->updateProductVisibilitiesForCategoryRelatedEntities(
            $category,
            $invertedVisibility,
            $this->accountGroupIdsWithInverseVisibility,
            $this->accountIdsWithInverseVisibility
        );
    }
}
