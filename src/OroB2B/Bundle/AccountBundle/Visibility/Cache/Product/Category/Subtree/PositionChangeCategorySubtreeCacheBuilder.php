<?php

namespace Oro\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree;

use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupCategoryVisibilityResolved;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\CatalogBundle\Entity\Category;

class PositionChangeCategorySubtreeCacheBuilder extends VisibilityChangeCategorySubtreeCacheBuilder
{
    /** @var array */
    protected $accountGroupIdsWithInverseVisibility = [];

    /** @var array */
    protected $accountIdsWithInverseVisibility = [];

    /** @var array */
    protected $accountGroupIdsWithConfigVisibility = [];

    /** @var array */
    protected $accountIdsWithConfigVisibility = [];

    /**
     * @param Category $category
     */
    public function categoryPositionChanged(Category $category)
    {
        $parentCategory = $category->getParentCategory();
        $visibility = $this->registry
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getFallbackToAllVisibility($parentCategory);

        $childCategoryIds = $this->getChildCategoryIdsForUpdate($category);
        $categoryIds = $this->getCategoryIdsForUpdate($category, $childCategoryIds);

        $this->updateCategoryVisibilityByCategory($categoryIds, $visibility);
        $this->updateProductVisibilityByCategory($categoryIds, $visibility);

        $this->updateAppropriateVisibilityRelatedEntities($category, $visibility);

        $this->updateInvertedVisibilityRelatedEntities($category, $visibility);
        $this->updateConfigVisibilityRelatedEntities($category);

        $this->clearChangedEntities();
    }

    protected function clearChangedEntities()
    {
        parent::clearChangedEntities();

        $this->accountGroupIdsWithInverseVisibility = [];
        $this->accountGroupIdsWithConfigVisibility = [];
        $this->accountIdsWithInverseVisibility = [];
        $this->accountIdsWithConfigVisibility = [];
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
        $accountGroupIdsWithConfigVisibility = [];

        $parentAccountGroupsVisibilities = $this->registry
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getRepository('OroAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getVisibilitiesForAccountGroups(
                $category->getParentCategory(),
                $accountGroupIdsWithFallbackToParent
            );

        foreach ($parentAccountGroupsVisibilities as $accountGroupId => $accountGroupVisibility) {
            if ($accountGroupVisibility === $visibility) {
                $accountGroupIdsForUpdate[] = $accountGroupId;
            } elseif ($accountGroupVisibility === BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG) {
                $accountGroupIdsWithConfigVisibility[] = $accountGroupId;
            } else {
                $accountGroupIdsWithInverseVisibility[] = $accountGroupId;
            }
        }

        $this->updateAccountGroupsCategoryVisibility(
            $category,
            $accountGroupIdsForUpdate,
            $visibility
        );

        $this->updateAccountGroupsProductVisibility(
            $category,
            $accountGroupIdsForUpdate,
            $visibility
        );

        $this->accountGroupIdsWithChangedVisibility[$category->getId()] = $accountGroupIdsForUpdate;
        $this->accountGroupIdsWithInverseVisibility = $accountGroupIdsWithInverseVisibility;
        $this->accountGroupIdsWithConfigVisibility = $accountGroupIdsWithConfigVisibility;
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
        $accountIdsWithConfigVisibility = [];

        $parentAccountsVisibilities = $this->registry
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->getRepository('OroAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->getVisibilitiesForAccounts($category->getParentCategory(), $accountIdsWithFallbackToParent);

        foreach ($parentAccountsVisibilities as $accountId => $accountVisibility) {
            if ($accountVisibility === $visibility) {
                $accountIdsForUpdate[] = $accountId;
            } elseif ($accountVisibility === BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG) {
                $accountIdsWithConfigVisibility[] = $accountId;
            } else {
                $accountIdsWithInverseVisibility[] = $accountId;
            }
        }

        $this->updateAccountsCategoryVisibility($category, $accountIdsForUpdate, $visibility);

        $this->updateAccountsProductVisibility($category, $accountIdsForUpdate, $visibility);

        $this->accountIdsWithChangedVisibility[$category->getId()] = $accountIdsForUpdate;
        $this->accountIdsWithInverseVisibility = $accountIdsWithInverseVisibility;
        $this->accountIdsWithConfigVisibility = $accountIdsWithConfigVisibility;
    }

    /**
     * @param Category $category
     * @param int $visibility
     */
    protected function updateInvertedVisibilityRelatedEntities(Category $category, $visibility)
    {
        $invertedVisibility = $visibility * -1;

        $this->updateAccountGroupsCategoryVisibility(
            $category,
            $this->accountGroupIdsWithInverseVisibility,
            $visibility
        );

        $this->updateAccountsCategoryVisibility(
            $category,
            $this->accountIdsWithInverseVisibility,
            $invertedVisibility
        );

        $this->updateAccountGroupsProductVisibility(
            $category,
            $this->accountGroupIdsWithInverseVisibility,
            $invertedVisibility
        );

        $this->updateAccountsProductVisibility(
            $category,
            $this->accountIdsWithInverseVisibility,
            $invertedVisibility
        );

        $this->updateProductVisibilitiesForCategoryRelatedEntities(
            $category,
            $invertedVisibility,
            $this->accountGroupIdsWithInverseVisibility,
            $this->accountIdsWithInverseVisibility
        );
    }

    /**
     * @param Category $category
     */
    protected function updateConfigVisibilityRelatedEntities(Category $category)
    {
        $this->updateAccountGroupsCategoryVisibility(
            $category,
            $this->accountGroupIdsWithInverseVisibility,
            BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG
        );

        $this->updateAccountsCategoryVisibility(
            $category,
            $this->accountIdsWithInverseVisibility,
            BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG
        );

        $this->updateAccountGroupsProductVisibility(
            $category,
            $this->accountGroupIdsWithConfigVisibility,
            BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG
        );

        $this->updateAccountsProductVisibility(
            $category,
            $this->accountIdsWithConfigVisibility,
            BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG
        );

        $this->updateProductVisibilitiesForCategoryRelatedEntities(
            $category,
            BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
            $this->accountGroupIdsWithConfigVisibility,
            $this->accountIdsWithInverseVisibility
        );
    }
}
