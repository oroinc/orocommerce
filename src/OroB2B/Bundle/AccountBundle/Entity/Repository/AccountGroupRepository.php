<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\CatalogBundle\Entity\Category;

class AccountGroupRepository extends EntityRepository
{
    /**
     * @param Category $category
     * @param string $visibility
     * @return array
     */
    public function getCategoryAccountGroupsByVisibility(Category $category, $visibility)
    {
        $qb = $this->createQueryBuilder('accountGroup');

        $qb->select('accountGroup')
            ->leftJoin(
                'OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility',
                'AccountGroupCategoryVisibility',
                Join::WITH,
                $qb->expr()->eq('AccountGroupCategoryVisibility.accountGroup', 'accountGroup')
            )
            ->where($qb->expr()->eq('AccountGroupCategoryVisibility.category', ':category'))
            ->andWhere($qb->expr()->eq('AccountGroupCategoryVisibility.visibility', ':visibility'))
            ->setParameters([
                'category' => $category,
                'visibility' => $visibility
            ]);

        return $qb->getQuery()->getResult();
    }
}
