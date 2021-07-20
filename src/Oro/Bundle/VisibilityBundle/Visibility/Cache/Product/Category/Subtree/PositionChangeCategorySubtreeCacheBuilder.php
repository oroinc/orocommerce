<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CategoryRepository;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerCategoryRepository;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerGroupCategoryRepository;

class PositionChangeCategorySubtreeCacheBuilder extends VisibilityChangeCategorySubtreeCacheBuilder
{
    /**
     * @var CustomerCategoryRepository
     */
    protected $customerCategoryRepository;

    /**
     * @var CustomerGroupCategoryRepository
     */
    protected $customerGroupCategoryRepository;

    /** @var array */
    protected $customerGroupIdsWithInverseVisibility = [];

    /** @var array */
    protected $customerIdsWithInverseVisibility = [];

    /** @var array */
    protected $customerGroupIdsWithConfigVisibility = [];

    /** @var array */
    protected $customerIdsWithConfigVisibility = [];

    /**
     * @param Category $category
     *
     * @return array|int[] Affected categories id
     */
    public function categoryPositionChanged(Category $category)
    {
        $parentCategory = $category->getParentCategory();
        /** @var CategoryRepository $repository */
        $repository = $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved');
        $visibility = $repository->getFallbackToAllVisibility($parentCategory);

        $childCategoryIds = $this->getChildCategoryIdsForUpdate($category);
        $categoryIds = $this->getCategoryIdsForUpdate($category, $childCategoryIds);

        $this->updateCategoryVisibilityByCategory($categoryIds, $visibility);
        $this->updateProductVisibilityByCategory($categoryIds, $visibility);

        $this->updateAppropriateVisibilityRelatedEntities($category, $visibility);

        $this->updateInvertedVisibilityRelatedEntities($category, $visibility);
        $this->updateConfigVisibilityRelatedEntities($category);

        $this->clearChangedEntities();

        return $categoryIds;
    }

    protected function clearChangedEntities()
    {
        parent::clearChangedEntities();

        $this->customerGroupIdsWithInverseVisibility = [];
        $this->customerGroupIdsWithConfigVisibility = [];
        $this->customerIdsWithInverseVisibility = [];
        $this->customerIdsWithConfigVisibility = [];
    }

    /**
     * @param Category $category
     * @param int $visibility
     */
    protected function updateAppropriateVisibilityRelatedEntities(Category $category, $visibility)
    {
        $this->updateCustomerGroupsAppropriateVisibility($category, $visibility);
        $this->updateCustomersAppropriateVisibility($category, $visibility);

        $this->updateProductVisibilitiesForCategoryRelatedEntities(
            $category,
            $visibility,
            $this->customerGroupIdsWithChangedVisibility[$category->getId()],
            $this->customerIdsWithChangedVisibility[$category->getId()]
        );
    }

    /**
     * @param Category $category
     * @param int $visibility
     */
    protected function updateCustomerGroupsAppropriateVisibility(Category $category, $visibility)
    {
        $customerGroupIdsForUpdate = $this->getCustomerGroupIdsFirstLevel($category);

        $customerGroupIdsWithFallbackToParent = $this
            ->getCategoryCustomerGroupIdsWithVisibilityFallbackToParent($category);

        $customerGroupIdsWithInverseVisibility = [];
        $customerGroupIdsWithConfigVisibility = [];

        $parentCustomerGroupsVisibilities = $this->getCustomerGroupCategoryRepository()
            ->getVisibilitiesForCustomerGroups(
                $this->scopeManager,
                $category->getParentCategory(),
                $customerGroupIdsWithFallbackToParent
            );

        foreach ($parentCustomerGroupsVisibilities as $customerGroupId => $customerGroupVisibility) {
            if ($customerGroupVisibility === $visibility) {
                $customerGroupIdsForUpdate[] = $customerGroupId;
            } elseif ($customerGroupVisibility === BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG) {
                $customerGroupIdsWithConfigVisibility[] = $customerGroupId;
            } else {
                $customerGroupIdsWithInverseVisibility[] = $customerGroupId;
            }
        }

        $this->updateCustomerGroupsCategoryVisibility(
            $category,
            $customerGroupIdsForUpdate,
            $visibility
        );

        $this->updateCustomerGroupsProductVisibility(
            $category,
            $customerGroupIdsForUpdate,
            $visibility
        );

        $this->customerGroupIdsWithChangedVisibility[$category->getId()] = $customerGroupIdsForUpdate;
        $this->customerGroupIdsWithInverseVisibility = $customerGroupIdsWithInverseVisibility;
        $this->customerGroupIdsWithConfigVisibility = $customerGroupIdsWithConfigVisibility;
    }

    /**
     * @param Category $category
     * @param int $visibility
     */
    protected function updateCustomersAppropriateVisibility(Category $category, $visibility)
    {
        $customerIdsForUpdate = $this->getCustomerIdsFirstLevel($category);
        $customerIdsWithFallbackToParent = $this->getCustomerIdsWithFallbackToParent($category);

        $customerIdsWithInverseVisibility = [];
        $customerIdsWithConfigVisibility = [];

        $parentCustomersVisibilities = $this->getCustomerCategoryRepository()
            ->getVisibilitiesForCustomers(
                $this->scopeManager,
                $category->getParentCategory(),
                $customerIdsWithFallbackToParent
            );

        foreach ($parentCustomersVisibilities as $customerId => $customerVisibility) {
            if ($customerVisibility === $visibility) {
                $customerIdsForUpdate[] = $customerId;
            } elseif ($customerVisibility === BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG) {
                $customerIdsWithConfigVisibility[] = $customerId;
            } else {
                $customerIdsWithInverseVisibility[] = $customerId;
            }
        }

        $this->updateCustomersCategoryVisibility($category, $customerIdsForUpdate, $visibility);

        $this->updateCustomersProductVisibility($category, $customerIdsForUpdate, $visibility);

        $this->customerIdsWithChangedVisibility[$category->getId()] = $customerIdsForUpdate;
        $this->customerIdsWithInverseVisibility = $customerIdsWithInverseVisibility;
        $this->customerIdsWithConfigVisibility = $customerIdsWithConfigVisibility;
    }

    /**
     * @param Category $category
     * @param int $visibility
     */
    protected function updateInvertedVisibilityRelatedEntities(Category $category, $visibility)
    {
        $invertedVisibility = $visibility * -1;

        $this->updateCustomerGroupsCategoryVisibility(
            $category,
            $this->customerGroupIdsWithInverseVisibility,
            $visibility
        );

        $this->updateCustomersCategoryVisibility(
            $category,
            $this->customerIdsWithInverseVisibility,
            $invertedVisibility
        );

        $this->updateCustomerGroupsProductVisibility(
            $category,
            $this->customerGroupIdsWithInverseVisibility,
            $invertedVisibility
        );

        $this->updateCustomersProductVisibility(
            $category,
            $this->customerIdsWithInverseVisibility,
            $invertedVisibility
        );

        $this->updateProductVisibilitiesForCategoryRelatedEntities(
            $category,
            $invertedVisibility,
            $this->customerGroupIdsWithInverseVisibility,
            $this->customerIdsWithInverseVisibility
        );
    }

    protected function updateConfigVisibilityRelatedEntities(Category $category)
    {
        $this->updateCustomerGroupsCategoryVisibility(
            $category,
            $this->customerGroupIdsWithInverseVisibility,
            BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG
        );

        $this->updateCustomersCategoryVisibility(
            $category,
            $this->customerIdsWithInverseVisibility,
            BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG
        );

        $this->updateCustomerGroupsProductVisibility(
            $category,
            $this->customerGroupIdsWithConfigVisibility,
            BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG
        );

        $this->updateCustomersProductVisibility(
            $category,
            $this->customerIdsWithConfigVisibility,
            BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG
        );

        $this->updateProductVisibilitiesForCategoryRelatedEntities(
            $category,
            BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
            $this->customerGroupIdsWithConfigVisibility,
            $this->customerIdsWithInverseVisibility
        );
    }

    public function setCustomerCategoryRepository(EntityRepository $repositoryHolder)
    {
        $this->customerCategoryRepository = $repositoryHolder;
    }

    /**
     * @return CustomerCategoryRepository
     */
    protected function getCustomerCategoryRepository()
    {
        return $this->customerCategoryRepository;
    }

    /**
     * @return CustomerGroupCategoryRepository
     */
    public function getCustomerGroupCategoryRepository()
    {
        return $this->customerGroupCategoryRepository;
    }

    /**
     * @param CustomerGroupCategoryRepository $customerGroupCategoryRepository
     */
    public function setCustomerGroupCategoryRepository($customerGroupCategoryRepository)
    {
        $this->customerGroupCategoryRepository = $customerGroupCategoryRepository;
    }
}
