<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Visibility\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

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
}
