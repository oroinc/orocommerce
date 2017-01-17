<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerGroupCategoryRepository;

class VisibilityChangeGroupSubtreeCacheBuilder extends AbstractRelatedEntitiesAwareSubtreeCacheBuilder
{
    /** @var Category */
    protected $category;

    /** @var CustomerGroup */
    protected $customerGroup;

    /**
     * @param Category $category
     * @param Scope $scope
     * @param int $visibility
     */
    public function resolveVisibilitySettings(Category $category, Scope $scope, $visibility)
    {
        $childCategoryIds = $this->getChildCategoryIdsForUpdate($category, $scope);
        $this->updateGroupCategoryVisibility($childCategoryIds, $visibility, $scope);

        $categoryIds = $this->getCategoryIdsForUpdate($category, $childCategoryIds);
        $productScopes = $this->scopeManager
            ->findRelatedScopeIds(CustomerGroupProductVisibility::VISIBILITY_TYPE, $scope);
        $this->updateProductVisibilityByCategory($categoryIds, $visibility, $productScopes);

        $this->category = $category;
        $this->customerGroup = $scope->getCustomerGroup();

        $this->updateProductVisibilitiesForCategoryRelatedEntities($category, $visibility);

        $this->clearChangedEntities();
    }

    /**
     * @param array $categoryIds
     * @param int $visibility
     * @param Scope $scope
     */
    protected function updateGroupCategoryVisibility(array $categoryIds, $visibility, Scope $scope)
    {
        if (!$categoryIds) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->update('OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved', 'agcvr')
            ->set('agcvr.visibility', $visibility)
            ->where($qb->expr()->eq('agcvr.scope', ':scope'))
            ->andWhere($qb->expr()->in('IDENTITY(agcvr.category)', ':categoryIds'))
            ->setParameters(['scope' => $scope, 'categoryIds' => $categoryIds]);

        $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function updateCustomerGroupsFirstLevel(Category $category, $visibility)
    {
        $customerGroupId = $this->customerGroup->getId();

        // if really first level - use customer group
        if ($category->getId() === $this->category->getId()) {
            return [$customerGroupId];
        // if not - check if category visibility has fallback to original category
        } else {
            $parentCategory = $category->getParentCategory();
            if ($parentCategory && !empty($this->customerGroupIdsWithChangedVisibility[$parentCategory->getId()])) {
                $visibility = $this->registry
                    ->getManagerForClass('OroVisibilityBundle:Visibility\CustomerGroupCategoryVisibility')
                    ->getRepository('OroVisibilityBundle:Visibility\CustomerGroupCategoryVisibility')
                    ->getCustomerGroupCategoryVisibility($this->customerGroup, $category);
                if ($visibility === CustomerGroupCategoryVisibility::PARENT_CATEGORY) {
                    return [$customerGroupId];
                }
            }
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function updateCustomersFirstLevel(Category $category, $visibility)
    {
        // if not first level - check if category has fallback to original category
        if ($category->getId() != $this->category->getId()
            && empty($this->customerGroupIdsWithChangedVisibility[$category->getId()])
        ) {
            return [];
        }

        $customerIdsForUpdate = $this->getCustomerIdsWithFallbackToCurrentGroup($category, $this->customerGroup);
        $this->updateCustomersProductVisibility($category, $customerIdsForUpdate, $visibility);

        return $customerIdsForUpdate;
    }

    /**
     * @param Category $category
     * @param CustomerGroup $customerGroup
     * @return array
     */
    protected function getCustomerIdsWithFallbackToCurrentGroup(Category $category, CustomerGroup $customerGroup)
    {
        /** @var Customer[] $groupCustomers */
        $groupCustomers = $customerGroup->getCustomers()->toArray();
        if (empty($groupCustomers)) {
            return [];
        }

        $groupCustomerIds = [];
        foreach ($groupCustomers as $customer) {
            $groupCustomerIds[] = $customer->getId();
        }
        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroCustomerBundle:Customer')
            ->createQueryBuilder();

        $qb->select('customer.id')
            ->from('OroCustomerBundle:Customer', 'customer')
            ->leftJoin('OroScopeBundle:Scope', 'scope', 'WITH', 'customer = scope.customer')
            ->leftJoin(
                'OroVisibilityBundle:Visibility\CustomerCategoryVisibility',
                'customerCategoryVisibility',
                'WITH',
                $qb->expr()->andX(
                    $qb->expr()->eq('customerCategoryVisibility.scope', 'scope'),
                    $qb->expr()->eq('customerCategoryVisibility.category', ':category')
                )
            )
            ->where($qb->expr()->in('customer', ':customers'))
            ->andWhere($qb->expr()->isNull('customerCategoryVisibility.visibility'))
            ->setParameters([
                'category' => $category,
                'customers' => $groupCustomers
            ]);
        $criteria = $this->scopeManager->getCriteriaForRelatedScopes(CustomerCategoryVisibility::VISIBILITY_TYPE, []);
        $criteria->applyToJoin($qb, 'scope');

        $scalarResult = $qb->getQuery()->getScalarResult();
        return array_map('current', $scalarResult);
    }

    /**
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    protected function restrictStaticFallback(QueryBuilder $qb)
    {
        return $qb->andWhere($qb->expr()->neq('cv.visibility', ':parentCategory'))
            ->setParameter('parentCategory', CustomerGroupCategoryVisibility::PARENT_CATEGORY);
    }

    /**
     * {@inheritdoc}
     */
    protected function restrictToParentFallback(QueryBuilder $qb)
    {
        return $qb->andWhere($qb->expr()->eq('cv.visibility', ':parentCategory'))
            ->setParameter('parentCategory', CustomerGroupCategoryVisibility::PARENT_CATEGORY);
    }

    /**
     * @param array $categoryIds
     * @param int $visibility
     * @param array $scopes
     */
    protected function updateProductVisibilityByCategory(array $categoryIds, $visibility, array $scopes)
    {
        if (!$categoryIds) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb = $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerGroupProductVisibilityResolved')
            ->createQueryBuilder();

        $qb->update('OroVisibilityBundle:VisibilityResolved\CustomerGroupProductVisibilityResolved', 'agpvr')
            ->set('agpvr.visibility', $visibility)
            ->where($qb->expr()->in('agpvr.scope', ':scopes'))
            ->andWhere($qb->expr()->in('IDENTITY(agpvr.category)', ':categoryIds'))
            ->setParameters(['scopes' => $scopes, 'categoryIds' => $categoryIds]);

        $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function joinCategoryVisibility(QueryBuilder $qb, $target)
    {
        return $qb->leftJoin(
            'OroVisibilityBundle:Visibility\CustomerGroupCategoryVisibility',
            'cv',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('node', 'cv.category'),
                $qb->expr()->eq('cv.scope', ':scope')
            )
        )
            ->setParameter('scope', $target);
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved');
    }

    /**
     * @return CustomerGroupCategoryRepository
     */
    protected function getRepository()
    {
        return $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved');
    }
}
