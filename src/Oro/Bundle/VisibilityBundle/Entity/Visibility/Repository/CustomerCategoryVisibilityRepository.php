<?php

namespace Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository;

use Oro\Bundle\CatalogBundle\Entity\Category;

class CustomerCategoryVisibilityRepository extends AbstractCategoryVisibilityRepository
{
    /**
     * @param Category $category
     * @param string $visibility
     * @param array $restrictedCustomerIds
     * @return array
     */
    public function getCategoryCustomerIdsByVisibility(
        Category $category,
        $visibility,
        array $restrictedCustomerIds = null
    ) {
        $qb = $this->createQueryBuilder('customerCategoryVisibility');

        $qb->select('IDENTITY(scope.customer) as customerId')
            ->join('customerCategoryVisibility.scope', 'scope')
            ->where($qb->expr()->eq('customerCategoryVisibility.category', ':category'))
            ->andWhere($qb->expr()->eq('customerCategoryVisibility.visibility', ':visibility'))
            ->setParameters([
                'category' => $category,
                'visibility' => $visibility,
            ]);

        if ($restrictedCustomerIds !== null) {
            $qb->andWhere($qb->expr()->in('scope.customer', ':restrictedCustomerIds'))
                ->setParameter('restrictedCustomerIds', $restrictedCustomerIds);
        }

        $ids = [];
        foreach ($qb->getQuery()->getScalarResult() as $visibility) {
            $ids[] = $visibility['customerId'];
        }
        // Return only customer ids
        return $ids;
    }
}
