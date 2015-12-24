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

        $categoryIds = $this->getCategoryIdsForUpdate($category, null);
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

        foreach ($accountGroupIdsWithFallbackToParent as $accountGroupId) {
            $accountGroupEm = $this->registry->getManagerForClass('OroB2BAccountBundle:AccountGroup');
            $accountGroup = $accountGroupEm->getReference('OroB2BAccountBundle:AccountGroup', $accountGroupId);
            $accountGroupVisibility = $this->convertVisibility(
                $this->categoryVisibilityResolver->isCategoryVisibleForAccountGroup($category, $accountGroup)
            );
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
        $accountForUpdate = $this->getAccountIdsFirstLevel($category);
        $accountIdsWithFallbackToParent = $this->getAccountIdsWithFallbackToParent($category);

        $accountIdsWithInverseVisibility = [];
        foreach ($accountIdsWithFallbackToParent as $accountId) {
            $accountEm = $this->registry->getManagerForClass('OroB2BAccountBundle:Account');
            $account = $accountEm->getReference('OroB2BAccountBundle:Account', $accountId);
            $accountVisibility = $this->convertVisibility(
                $this->categoryVisibilityResolver->isCategoryVisibleForAccount($category, $account)
            );
            if ($accountVisibility === $visibility) {
                $accountForUpdate[] = $account;
            } else {
                $accountIdsWithInverseVisibility[] = $account;
            }
        }

        $this->updateAccountsProductVisibility($category, $accountForUpdate, $visibility);

        $this->accountIdsWithChangedVisibility[$category->getId()] = $accountForUpdate;
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
