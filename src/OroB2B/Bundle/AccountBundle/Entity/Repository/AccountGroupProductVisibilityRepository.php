<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;

class AccountGroupProductVisibilityRepository extends EntityRepository
{
    const BATCH_SIZE = 1000;

    /**
     * Delete from AccountGroupProductVisibility visibilities with fallback to 'category' when category is absent
     */
    public function setToDefaultValueProductAccountGroupProductVisibilityForProductsWithoutCategory()
    {
        $qb = $this->createQueryBuilder('accountGroupProductVisibility');
        $qb->delete()
            ->where($qb->expr()->in('accountGroupProductVisibility.id', ':accountGroupProductVisibilityIds'));

        while ($accountGroupProductVisibilityIds = $this->getAccountGroupProductVisibilityForDelete()) {
            $qb->getQuery()->execute(['accountGroupProductVisibilityIds' => $accountGroupProductVisibilityIds]);
        }
    }

    /**
     * @return int[]
     */
    protected function getAccountGroupProductVisibilityForDelete()
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
