<?php

namespace Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository;

use Oro\Bundle\CatalogBundle\Entity\Category;

class AccountCategoryVisibilityRepository extends AbstractCategoryVisibilityRepository
{
    /**
     * @param Category $category
     * @param string $visibility
     * @param array $restrictedAccountIds
     * @return array
     */
    public function getCategoryAccountIdsByVisibility(
        Category $category,
        $visibility,
        array $restrictedAccountIds = null
    ) {
        $qb = $this->createQueryBuilder('accountCategoryVisibility');

        $qb->select('IDENTITY(scope.account) as accountId')
            ->join('accountCategoryVisibility.scope', 'scope')
            ->where($qb->expr()->eq('accountCategoryVisibility.category', ':category'))
            ->andWhere($qb->expr()->eq('accountCategoryVisibility.visibility', ':visibility'))
            ->setParameters([
                'category' => $category,
                'visibility' => $visibility,
            ]);

        if ($restrictedAccountIds !== null) {
            $qb->andWhere($qb->expr()->in('scope.account', ':restrictedAccountIds'))
                ->setParameter('restrictedAccountIds', $restrictedAccountIds);
        }

        $ids = [];
        foreach ($qb->getQuery()->getScalarResult() as $visibility) {
            $ids[] = $visibility['accountId'];
        }
        // Return only account ids
        return $ids;
    }
}
