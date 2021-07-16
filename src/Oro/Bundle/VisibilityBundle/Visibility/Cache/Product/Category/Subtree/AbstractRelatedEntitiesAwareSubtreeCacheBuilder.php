<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;

abstract class AbstractRelatedEntitiesAwareSubtreeCacheBuilder extends AbstractSubtreeCacheBuilder
{
    /** @var array */
    protected $customerGroupIdsWithChangedVisibility = [];

    /** @var array */
    protected $customerIdsWithChangedVisibility = [];

    /**
     * @param Category $category
     * @param int $visibility
     * @return array
     */
    abstract protected function updateCustomerGroupsFirstLevel(Category $category, $visibility);

    /**
     * @param Category $category
     * @param int $visibility
     * @return array
     */
    abstract protected function updateCustomersFirstLevel(Category $category, $visibility);

    protected function clearChangedEntities()
    {
        $this->customerGroupIdsWithChangedVisibility = [];
        $this->customerIdsWithChangedVisibility = [];
    }

    /**
     * @param Category $category
     * @param int $visibility
     * @param array|null $customerGroupIdsWithChangedVisibility
     * @param array|null $customerIdsWithChangedVisibility
     */
    protected function updateProductVisibilitiesForCategoryRelatedEntities(
        Category $category,
        $visibility,
        array $customerGroupIdsWithChangedVisibility = null,
        array $customerIdsWithChangedVisibility = null
    ) {
        if ($customerGroupIdsWithChangedVisibility === null) {
            $this->customerGroupIdsWithChangedVisibility[$category->getId()]
                = $this->updateCustomerGroupsFirstLevel($category, $visibility);
        } else {
            $this->customerGroupIdsWithChangedVisibility[$category->getId()]
                = $customerGroupIdsWithChangedVisibility;
        }

        if ($customerIdsWithChangedVisibility === null) {
            $this->customerIdsWithChangedVisibility[$category->getId()]
                = $this->updateCustomersFirstLevel($category, $visibility);
        } else {
            $this->customerIdsWithChangedVisibility[$category->getId()]
                = $customerIdsWithChangedVisibility;
        }

        if (!$this->customerGroupIdsWithChangedVisibility[$category->getId()] &&
            !$this->customerIdsWithChangedVisibility[$category->getId()]
        ) {
            return;
        }

        $childCategories = $this->registry
            ->getManagerForClass('OroCatalogBundle:Category')
            ->getRepository('OroCatalogBundle:Category')
            ->getAllChildCategories($category);

        $childCategoryLevels = [];
        /** @var Category[] $childCategories */
        foreach ($childCategories as $childCategory) {
            $childCategoryLevels[$childCategory->getLevel()][] = $childCategory;
        }

        $firstSubCategoryLevel = $category->getLevel() + 1;
        if (!empty($childCategoryLevels)) {
            for ($level = $firstSubCategoryLevel; $level <= max(array_keys($childCategoryLevels)); $level++) {
                $this->updateLevelCategories($childCategoryLevels[$level], $visibility);
            }
        }

        unset($childCategories);

        $childCategoriesWithFallbackToParent = $this->getDirectChildCategoriesWithFallbackToParent($category);
        foreach ($childCategoriesWithFallbackToParent as $category) {
            $this->updateProductVisibilitiesForCategoryRelatedEntities($category, $visibility);
        }
    }

    /**
     * @param Category[] $levelCategories
     * @param int $visibility
     */
    protected function updateLevelCategories($levelCategories, $visibility)
    {
        /** @var Category $levelCategory */
        foreach ($levelCategories as $levelCategory) {
            $parentCustomerGroups
                = $this->customerGroupIdsWithChangedVisibility[$levelCategory->getParentCategory()->getId()];
            $updatedCustomerGroupIds = $this
                ->getCategoryCustomerGroupIdsWithVisibilityFallbackToParent($levelCategory, $parentCustomerGroups);

            /**
             * Cache updated customer groups for current subcategory into appropriate section
             */
            $this->customerGroupIdsWithChangedVisibility[$levelCategory->getId()] = $updatedCustomerGroupIds;

            if (!empty($updatedCustomerGroupIds)) {
                $updatedCustomerGroupIdsWithoutConfigFallback = $this
                    ->removeIdsWithConfigFallback($levelCategory, $updatedCustomerGroupIds);
                $this->updateCustomerGroupsProductVisibility($levelCategory, $updatedCustomerGroupIds, $visibility);
                $this->updateCustomerGroupsCategoryVisibility(
                    $levelCategory,
                    $updatedCustomerGroupIdsWithoutConfigFallback,
                    $visibility
                );
            }

            $parentCustomers = $this->customerIdsWithChangedVisibility[$levelCategory->getParentCategory()->getId()];
            $customerIdsForUpdate = $this->getCustomerIdsWithFallbackToParent($levelCategory, $parentCustomers);

            if (!empty($updatedCustomerGroupIds)) {
                $customerIdsForUpdate = array_merge(
                    $customerIdsForUpdate,
                    $this->getCustomerIdsForUpdate($levelCategory, $updatedCustomerGroupIds)
                );
            }

            /**
             * Cache updated customers for current subcategory into appropriate section
             */
            $this->customerIdsWithChangedVisibility[$levelCategory->getId()] = $customerIdsForUpdate;

            if (!empty($customerIdsForUpdate)) {
                $this->updateCustomersCategoryVisibility($levelCategory, $customerIdsForUpdate, $visibility);
                $this->updateCustomersProductVisibility($levelCategory, $customerIdsForUpdate, $visibility);
            }
        }
    }

    /**
     * @param Category $category
     * @param array $customerGroupIds
     * @return array
     */
    protected function removeIdsWithConfigFallback(Category $category, array $customerGroupIds)
    {
        $customerGroupsCategoryVisibilities = $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved')
            ->getVisibilitiesForCustomerGroups($this->scopeManager, $category, $customerGroupIds);

        $customerGroupsWithConfigCallbackIds = [];
        foreach ($customerGroupsCategoryVisibilities as $customerGroupId => $visibility) {
            if ($visibility == BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG) {
                $customerGroupsWithConfigCallbackIds[] = $customerGroupId;
            }
        }

        return array_diff($customerGroupIds, $customerGroupsWithConfigCallbackIds);
    }

    /**
     * @param Category $category
     * @param array $restrictedCustomerGroupIds
     * @return array
     */
    protected function getCategoryCustomerGroupIdsWithVisibilityFallbackToParent(
        Category $category,
        array $restrictedCustomerGroupIds = null
    ) {
        return $this->registry
            ->getManagerForClass(CustomerGroupCategoryVisibility::class)
            ->getRepository(CustomerGroupCategoryVisibility::class)
            ->getCategoryCustomerGroupIdsByVisibility(
                $category,
                CustomerGroupCategoryVisibility::PARENT_CATEGORY,
                $restrictedCustomerGroupIds
            );
    }

    /**
     * @param Category $category
     * @param array $restrictedCustomerIds
     * @return array
     */
    protected function getCustomerIdsWithFallbackToParent(Category $category, array $restrictedCustomerIds = null)
    {
        return $this->registry
            ->getManagerForClass(CustomerCategoryVisibility::class)
            ->getRepository(CustomerCategoryVisibility::class)
            ->getCategoryCustomerIdsByVisibility(
                $category,
                CustomerCategoryVisibility::PARENT_CATEGORY,
                $restrictedCustomerIds
            );
    }

    /**
     * @param Category $category
     * @return array
     */
    protected function getCustomerIdsWithFallbackToAll(Category $category)
    {
        return $this->registry
            ->getManagerForClass(CustomerCategoryVisibility::class)
            ->getRepository(CustomerCategoryVisibility::class)
            ->getCategoryCustomerIdsByVisibility($category, CustomerCategoryVisibility::CATEGORY);
    }

    /**
     * @param Category $category
     * @param array $customerGroupIds
     * @return array
     */
    protected function getCustomerIdsForUpdate(Category $category, array $customerGroupIds)
    {
        if (!$customerGroupIds) {
            return [];
        }

        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroCustomerBundle:Customer')
            ->createQueryBuilder();

        /** @var QueryBuilder $subQb */
        $subQb = $this->registry
            ->getManagerForClass('OroVisibilityBundle:Visibility\CustomerCategoryVisibility')
            ->createQueryBuilder();

        $subQb->select('1')
            ->from('OroVisibilityBundle:Visibility\CustomerCategoryVisibility', 'customerCategoryVisibility')
            ->join('customerCategoryVisibility.scope', 'scope')
            ->join('scope.customer', 'scopeCustomer')
            ->where($qb->expr()->eq('customerCategoryVisibility.category', ':category'))
            ->andWhere($qb->expr()->eq('scope.customer', 'customer.id'));

        $qb->select('customer.id')
            ->from('OroCustomerBundle:Customer', 'customer')
            ->where($qb->expr()->not($qb->expr()->exists($subQb->getDQL())))
            ->andWhere($qb->expr()->in('customer.group', ':customerGroupIds'))

            ->setParameters([
                'category' => $category,
                'customerGroupIds' => $customerGroupIds
            ]);

        return array_map('current', $qb->getQuery()->getScalarResult());
    }

    /**
     * @param Category $category
     * @return Category[]
     */
    protected function getDirectChildCategoriesWithFallbackToParent(Category $category)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroCatalogBundle:Category')
            ->getRepository('OroCatalogBundle:Category')
            ->getChildrenQueryBuilderPartial($category);

        $qb->leftJoin(
            'OroVisibilityBundle:Visibility\CategoryVisibility',
            'categoryVisibility',
            Join::WITH,
            $qb->expr()->eq('node.id', 'categoryVisibility.category')
        )
        ->andWhere($qb->expr()->isNull('categoryVisibility.visibility'))
        ->andWhere($qb->expr()->eq('node.parentCategory', ':category'))
        ->setParameter('category', $category);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Category $category
     * @param array $customerGroupIds
     * @param int $visibility
     */
    protected function updateCustomerGroupsProductVisibility(Category $category, $customerGroupIds, $visibility)
    {
        if (!$customerGroupIds) {
            return;
        }
        $scopes = $this->scopeManager->findRelatedScopeIds(
            CustomerGroupProductVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $customerGroupIds]
        );
        if (!$scopes) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerGroupProductVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroVisibilityBundle:VisibilityResolved\CustomerGroupProductVisibilityResolved', 'agpvr')
            ->set('agpvr.visibility', ':visibility')
            ->where($qb->expr()->eq('agpvr.category', ':category'))
            ->andWhere($qb->expr()->in('agpvr.scope', ':scopes'))
            ->setParameters([
                'scopes' => $scopes,
                'category' => $category,
                'visibility' => $visibility
            ]);

        $qb->getQuery()->execute();
    }

    protected function updateCustomersProductVisibility(Category $category, array $customerIds, $visibility)
    {
        if (!$customerIds) {
            return;
        }
        $scopes = $this->scopeManager->findRelatedScopeIds(
            CustomerProductVisibility::VISIBILITY_TYPE,
            ['customer' => $customerIds]
        );
        if (!$scopes) {
            return;
        }
        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerProductVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroVisibilityBundle:VisibilityResolved\CustomerProductVisibilityResolved', 'apvr')
            ->set('apvr.visibility', ':visibility')
            ->where($qb->expr()->eq('apvr.category', ':category'))
            ->andWhere($qb->expr()->in('apvr.scope', ':scopes'))
            ->setParameters([
                'scopes' => $scopes,
                'category' => $category,
                'visibility' => $visibility
            ]);

        $qb->getQuery()->execute();
    }

    protected function updateCustomersCategoryVisibility(Category $category, array $customerIds, $visibility)
    {
        if (!$customerIds) {
            return;
        }
        $scopes = $this->scopeManager->findRelatedScopeIds(
            CustomerCategoryVisibility::VISIBILITY_TYPE,
            ['customer' => $customerIds]
        );
        if (!$scopes) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved', 'acvr')
            ->set('acvr.visibility', ':visibility')
            ->where($qb->expr()->eq('acvr.category', ':category'))
            ->andWhere($qb->expr()->in('acvr.scope', ':scopes'))
            ->setParameters([
                'scopes' => $scopes,
                'category' => $category,
                'visibility' => $visibility
            ]);

        $qb->getQuery()->execute();
    }

    protected function updateCustomerGroupsCategoryVisibility(
        Category $category,
        array $customerGroupIds,
        $visibility
    ) {
        if (!$customerGroupIds) {
            return;
        }
        $scopes = $this->scopeManager->findRelatedScopeIds(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $customerGroupIds]
        );
        if (!$scopes) {
            return;
        }
        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved', 'agcvr')
            ->set('agcvr.visibility', ':visibility')
            ->where($qb->expr()->eq('agcvr.category', ':category'))
            ->andWhere($qb->expr()->in('agcvr.scope', ':scopes'))
            ->setParameters([
                'scopes' => $scopes,
                'category' => $category,
                'visibility' => $visibility
            ]);

        $qb->getQuery()->execute();
    }
}
