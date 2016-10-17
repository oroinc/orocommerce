<?php

namespace Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository;

use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\CatalogBundle\Entity\Category;

class AccountGroupCategoryVisibilityRepository extends AbstractCategoryVisibilityRepository
{
    /**
     * @param AccountGroup $accountGroup
     * @param Category $category
     * @return string|null
     */
    public function getAccountGroupCategoryVisibility(AccountGroup $accountGroup, Category $category)
    {
        $result = $this->createQueryBuilder('accountGroupCategoryVisibility')
            ->select('accountGroupCategoryVisibility.visibility')
            ->andWhere('accountGroupCategoryVisibility.accountGroup = :accountGroup')
            ->andWhere('accountGroupCategoryVisibility.category = :category')
            ->setParameter('accountGroup', $accountGroup)
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
     * @param array $restrictedAccountGroupIds
     * @return array
     */
    public function getCategoryAccountGroupIdsByVisibility(
        Category $category,
        $visibility,
        array $restrictedAccountGroupIds = null
    ) {
        $qb = $this->createQueryBuilder('visibility');

        $qb->select('IDENTITY(scope.accountGroup) as accountGroupId')
            ->join('visibility.scope', 'scope')
            ->where($qb->expr()->eq('visibility.category', ':category'))
            ->andWhere($qb->expr()->eq('visibility.visibility', ':visibility'))
            ->setParameters([
                'category' => $category,
                'visibility' => $visibility
            ]);

        if ($restrictedAccountGroupIds !== null) {
            $qb->andWhere($qb->expr()->in('scope.accountGroup', ':restrictedAccountGroupIds'))
                ->setParameter('restrictedAccountGroupIds', $restrictedAccountGroupIds);
        }
        $ids = [];
        foreach ($qb->getQuery()->getScalarResult() as $accountGroup) {
            $ids[] = $accountGroup['accountGroupId'];
        }

        return $ids;
    }
}
