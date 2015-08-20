<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility;

class AccountCategoryVisibilityRepository extends EntityRepository
{
    /**
     * @param AccountGroup[] $accounts
     * @param Category $category
     *
     * @return AccountCategoryVisibility[]|ArrayCollection
     */
    public function findForAccounts(array $accounts, Category $category)
    {
        $qb = $this->createQueryBuilder('v');

        $visibilities = $qb
            ->where($qb->expr()->in('v.account', ':accounts'))
            ->andWhere('v.category = :category')
            ->setParameter('accounts', $accounts)
            ->setParameter('category', $category)
            ->getQuery()
            ->execute();

        return new ArrayCollection($visibilities);
    }
}
