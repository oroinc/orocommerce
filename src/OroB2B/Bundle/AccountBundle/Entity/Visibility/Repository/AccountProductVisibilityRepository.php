<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Visibility\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class AccountProductVisibilityRepository extends EntityRepository
{
    const BATCH_SIZE = 1000;
    
    /**
     * Return categories list of products which has "category" fallback in AccountProductVisibility
     *
     * @return Category[]
     */
    public function getCategoriesByAccountProductVisibility()
    {
        $result = $this->getEntityManager()
            ->getRepository('OroB2BCatalogBundle:Category')
            ->createQueryBuilder('category')
            ->select('partial category.{id}')
            ->distinct()
            ->innerJoin('category.products', 'product')
            ->innerJoin(
                'OroB2BAccountBundle:Visibility\AccountProductVisibility',
                'apv',
                Join::WITH,
                'apv.product = product AND apv.visibility = :category'
            )
            ->setParameter('category', AccountProductVisibility::CATEGORY)
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * @return Account[]
     */
    public function getAccountsForCategoryType()
    {
        return $this->getEntityManager()
            ->getRepository('OroB2BAccountBundle:Account')
            ->createQueryBuilder('account')
            ->select('partial account.{id}')
            ->distinct()
            ->innerJoin(
                'OroB2BAccountBundle:Visibility\AccountProductVisibility',
                'apv',
                Join::WITH,
                'apv.account = account AND apv.visibility = :category'
            )
            ->where('apv.visibility = :category')
            ->setParameter('category', AccountProductVisibility::CATEGORY)
            ->getQuery()
            ->getResult();
    }

    /**
     * Delete from AccountProductVisibility visibilities with fallback to 'category' when category is absent
     */
    public function setToDefaultWithoutCategory()
    {
        $qb = $this->createQueryBuilder('accountProductVisibility');
        $qb->delete()
            ->where($qb->expr()->in('accountProductVisibility.id', ':accountProductVisibilityIds'));

        while ($accountProductVisibilityIds = $this->getVisibilityIdsForDelete()) {
            $qb->getQuery()->execute(['accountProductVisibilityIds' => $accountProductVisibilityIds]);
        }
    }

    /**
     * @return int[]
     */
    protected function getVisibilityIdsForDelete()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $result = $qb->select('accountProductVisibility.id')
            ->from($this->getEntityName(), 'accountProductVisibility')
            ->leftJoin('accountProductVisibility.product', 'product')
            ->leftJoin(
                'OroB2BCatalogBundle:Category',
                'category',
                Join::WITH,
                $qb->expr()->isMemberOf('product', 'category.products')
            )
            ->where($qb->expr()->isNull('category.id'))
            ->andWhere($qb->expr()->eq('accountProductVisibility.visibility', ':visibility'))
            ->setMaxResults(self::BATCH_SIZE)
            ->setParameter('visibility', AccountProductVisibility::CATEGORY)
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $result);
    }
}
