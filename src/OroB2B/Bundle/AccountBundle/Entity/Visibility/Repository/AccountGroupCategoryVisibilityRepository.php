<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Visibility\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;

class AccountGroupCategoryVisibilityRepository extends EntityRepository
{
    /**
     * @param AccountGroup $accountGroup
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getCategoryWithVisibilitiesForAccountGroup(AccountGroup $accountGroup)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder
            ->select(
                'c.id as category_id',
                'IDENTITY(c.parentCategory) as category_parent_id',
                'categoryVisibility.visibility',
                'accountGroupCategoryVisibility.visibility as account_group_visibility'
            )
            ->from('OroB2BCatalogBundle:Category', 'c')
            ->leftJoin(
                'OroB2BAccountBundle:Visibility\CategoryVisibility',
                'categoryVisibility',
                Join::WITH,
                $queryBuilder->expr()->eq('categoryVisibility.category', 'c')
            )
            ->leftJoin(
                'OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility',
                'accountGroupCategoryVisibility',
                Join::WITH,
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('accountGroupCategoryVisibility.category', 'c'),
                    $queryBuilder->expr()->eq('accountGroupCategoryVisibility.accountGroup', ':accountGroup')
                )
            )
            ->setParameter('accountGroup', $accountGroup)
            ->orderBy('c.level', 'ASC');

        return $queryBuilder;
    }

    /**
     * [
     *      [
     *          'visibility_id' => <int>,
     *          'parent_visibility_id' => <int|null>,
     *          'parent_visibility_visibility' => <string|null>,
     *          'category_id' => <int>,
     *          'parent_category_id' => <int|null>,
     *      ],
     *      ...
     * ]
     *
     * @return array
     */
    public function getParentCategoryVisibilities()
    {
        $qb = $this->createQueryBuilder('agcv');

        return $qb->select(
            'agcv.id as visibility_id',
            'agcv_parent.id as parent_visibility_id',
            'agcv_parent.visibility as parent_visibility_visibility',
            'c.id as category_id',
            'IDENTITY(c.parentCategory) as parent_category_id'
        )
        // join to category that includes only parent category entities
        ->innerJoin(
            'agcv.category',
            'c',
            'WITH',
            'agcv.visibility = ' . $qb->expr()->literal(AccountGroupCategoryVisibility::PARENT_CATEGORY)
        )
        // join to parent category visibility
        ->leftJoin(
            $this->getEntityName(),
            'agcv_parent',
            'WITH',
            'IDENTITY(agcv_parent.accountGroup) = IDENTITY(agcv.accountGroup) AND ' .
            'IDENTITY(agcv_parent.category) = IDENTITY(c.parentCategory)'
        )
        // order is important to make sure that higher level categories will be processed first
        ->addOrderBy('c.level', 'ASC')
        ->addOrderBy('c.left', 'ASC')
        ->getQuery()
        ->getScalarResult();
    }
}
