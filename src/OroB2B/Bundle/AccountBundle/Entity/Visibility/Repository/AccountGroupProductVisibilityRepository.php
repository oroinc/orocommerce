<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Visibility\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class AccountGroupProductVisibilityRepository extends EntityRepository
{
    const BATCH_SIZE = 1000;

    /**
     * Return categories list of categories of products which has "category" fallback in AccountGroupProductVisibility
     *
     * @return integer[]
     */
    public function getCategoryIdsByAccountGroupProductVisibility()
    {
        $result = $this->getEntityManager()
            ->getRepository('OroB2BCatalogBundle:Category')
            ->createQueryBuilder('category')
            ->select('category.id')
            ->distinct()
            ->innerJoin('category.products', 'product')
            ->innerJoin(
                'OroB2BAccountBundle:Visibility\AccountGroupProductVisibility',
                'agpv',
                Join::WITH,
                'agpv.product = product AND agpv.visibility = :category'
            )
            ->setParameter('category', AccountGroupProductVisibility::CATEGORY)
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $result);
    }

    /**
     * @return AccountGroup[]
     */
    public function getAccountGroupsWithCategoryVisibiliy()
    {
        return $this->getEntityManager()
            ->getRepository('OroB2BAccountBundle:AccountGroup')
            ->createQueryBuilder('accountGroup')
            ->select('partial accountGroup.{id}')
            ->distinct()
            ->innerJoin(
                'OroB2BAccountBundle:Visibility\AccountGroupProductVisibility',
                'agpv',
                Join::WITH,
                'agpv.accountGroup = accountGroup AND agpv.visibility = :category'
            )
            ->where('agpv.visibility = :category')
            ->setParameter('category', AccountGroupProductVisibility::CATEGORY)
            ->getQuery()
            ->getResult();
    }

    /**
     * Delete from AccountGroupProductVisibility visibilities with fallback to 'category' when category is absent
     */
    public function setToDefaultWithoutCategory()
    {
        $qb = $this->createQueryBuilder('accountGroupProductVisibility');
        $qb->delete()
            ->where($qb->expr()->in('accountGroupProductVisibility.id', ':accountGroupProductVisibilityIds'));

        while ($accountGroupProductVisibilityIds = $this->getVisibilityIdsForDelete()) {
            $qb->getQuery()->execute(['accountGroupProductVisibilityIds' => $accountGroupProductVisibilityIds]);
        }
    }

    /**
     * @return int[]
     */
    protected function getVisibilityIdsForDelete()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $accountGroupProductVisibilities = $qb
            ->select('accountGroupProductVisibility.id')
            ->from($this->getEntityName(), 'accountGroupProductVisibility')
            ->leftJoin('accountGroupProductVisibility.product', 'product')
            ->leftJoin(
                'OroB2BCatalogBundle:Category',
                'category',
                Join::WITH,
                $qb->expr()->isMemberOf('product', 'category.products')
            )
            ->where($qb->expr()->isNull('category.id'))
            ->andWhere($qb->expr()->eq('accountGroupProductVisibility.visibility', ':visibility'))
            ->setMaxResults(self::BATCH_SIZE)
            ->setParameter('visibility', AccountGroupProductVisibility::CATEGORY)
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $accountGroupProductVisibilities);
    }
}
