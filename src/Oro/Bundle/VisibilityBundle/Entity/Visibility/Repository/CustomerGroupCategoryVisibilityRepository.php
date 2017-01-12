<?php

namespace Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;

class CustomerGroupCategoryVisibilityRepository extends AbstractCategoryVisibilityRepository
{
    /**
     * @param CustomerGroup $customerGroup
     * @param Category $category
     * @return string|null
     */
    public function getCustomerGroupCategoryVisibility(CustomerGroup $customerGroup, Category $category)
    {
        $result = $this->createQueryBuilder('customerGroupCategoryVisibility')
            ->select('customerGroupCategoryVisibility.visibility')
            ->join('customerGroupCategoryVisibility.scope', 'scope')
            ->andWhere('scope.customerGroup = :customerGroup')
            ->andWhere('customerGroupCategoryVisibility.category = :category')
            ->setParameter('customerGroup', $customerGroup)
            ->setParameter('category', $category)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($result) {
            return $result['visibility'];
        } else {
            return null;
        }
    }

    /**
     * @param Category $category
     * @param string $visibility
     * @param array $restrictedCustomerGroupIds
     * @return array
     */
    public function getCategoryCustomerGroupIdsByVisibility(
        Category $category,
        $visibility,
        array $restrictedCustomerGroupIds = null
    ) {
        $qb = $this->createQueryBuilder('visibility');

        $qb->select('IDENTITY(scope.customerGroup) as customerGroupId')
            ->join('visibility.scope', 'scope')
            ->where($qb->expr()->eq('visibility.category', ':category'))
            ->andWhere($qb->expr()->eq('visibility.visibility', ':visibility'))
            ->setParameters([
                'category' => $category,
                'visibility' => $visibility
            ]);

        if ($restrictedCustomerGroupIds !== null) {
            $qb->andWhere($qb->expr()->in('scope.customerGroup', ':restrictedCustomerGroupIds'))
                ->setParameter('restrictedCustomerGroupIds', $restrictedCustomerGroupIds);
        }
        $ids = [];
        foreach ($qb->getQuery()->getScalarResult() as $customerGroup) {
            $ids[] = $customerGroup['customerGroupId'];
        }

        return $ids;
    }
}
