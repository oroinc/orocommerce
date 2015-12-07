<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\Subtree;

use OroB2B\Bundle\CatalogBundle\Entity\Category;

class PositionChangeCategorySubtreeCacheBuilder extends VisibilityChangeCategorySubtreeCacheBuilder
{
    /**
     * @param Category $category
     */
    public function categoryPositionChanged(Category $category)
    {
        $visibility = $this->categoryVisibilityResolver->isCategoryVisible($category);
        $visibility = $this->convertVisibility($visibility);

        $categoryIds = $this->getCategoryIdsForUpdate($category, null);
        $this->updateProductVisibilityByCategory($categoryIds, $visibility);

        $accountGroupsForUpdate = $this->getAccountGroupsFirstLevel($category);

        $accountGroupsWithFallbackToParent = $this->getCategoryAccountGroupsWithVisibilityFallbackToParent($category);

        $accountGroupsWithInverseVisibility = [];

        foreach ($accountGroupsWithFallbackToParent as $accountGroup) {
            $accountGroupVisibility
                = $this->categoryVisibilityResolver->isCategoryVisibleForAccountGroup($category, $accountGroup);
            if ($accountGroupVisibility === $visibility) {
                $accountGroupsForUpdate[] = $accountGroup;
            } else {
                $accountGroupsWithInverseVisibility[] = $accountGroup;
            }
        }

        $this->updateAccountGroupsProductVisibility($category, $accountGroupsForUpdate, $visibility);

        $this->accountGroupsWithChangedVisibility[$category->getId()] = $accountGroupsForUpdate;

        $accountForUpdate = $this->getAccountsFirstLevel($category);
        $accountsWithFallbackToParent = $this->getAccountsWithFallbackToParent($category);

        $accountsWithInverseVisibility = [];
        foreach ($accountsWithFallbackToParent as $account) {
            $accountVisibility
                = $this->categoryVisibilityResolver->isCategoryVisibleForAccount($category, $account);
            if ($accountVisibility === $visibility) {
                $accountForUpdate[] = $account;
            } else {
                $accountsWithInverseVisibility[] = $account;
            }
        }

        $this->updateAccountsProductVisibility($category, $accountForUpdate, $visibility);

        $this->accountsWithChangedVisibility[$category->getId()] = $accountForUpdate;
        $this->updateProductVisibilitiesForCategoryRelatedEntities($category, $visibility);

        $invertedVisibility = $visibility * -1;

        $this->updateAccountGroupsProductVisibility(
            $category,
            $accountGroupsWithInverseVisibility,
            $invertedVisibility
        );

        $this->updateAccountsProductVisibility($category, $accountsWithInverseVisibility, $invertedVisibility);

        $this->accountGroupsWithChangedVisibility[$category->getId()] = $accountGroupsWithInverseVisibility;
        $this->accountsWithChangedVisibility[$category->getId()] = $accountsWithInverseVisibility;

        $this->updateProductVisibilitiesForCategoryRelatedEntities($category, $invertedVisibility);
    }

    /**
     * @param Category $category
     * @return array
     */
    protected function getVisibilityAccountGroupsForCurrentCategory(Category $category)
    {
        $accountGroups = $this->registry->getManagerForClass('OroB2BAccountBundle:AccountGroup')
            ->getRepository('OroB2BAccountBundle:AccountGroup')
            ->findAll();

        $result = [];
        foreach ($accountGroups as $accountGroup) {
            $visibility = $this->categoryVisibilityResolver->isCategoryVisibleForAccountGroup($category, $accountGroup);
            if ($visibility) {
                $result['visible'][] = $accountGroup->getId();
            } else {
                $result['hidden'][] = $accountGroup->getId();
            }
        }

        return $result;
    }

    /**
     * @param Category $category
     * @return array
     */
    protected function getVisibilityAccountsForCurrentCategory(Category $category)
    {
        $accounts = $this->registry->getManagerForClass('OroB2BAccountBundle:Account')
            ->getRepository('OroB2BAccountBundle:Account')
            ->findAll();

        $result = [];
        foreach ($accounts as $account) {
            $visibility = $this->categoryVisibilityResolver->isCategoryVisibleForAccount($category, $account);
            if ($visibility) {
                $result['visible'][] = $account->getId();
            } else {
                $result['hidden'][] = $account->getId();
            }
        }

        return $result;
    }
}
