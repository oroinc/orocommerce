<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroupCategoryVisibility;

class AccountGroupCategoryVisibilityRepository extends EntityRepository
{
    /**
     * @param Account[] $accountGroups
     * @param Category $category
     *
     * @return AccountGroupCategoryVisibility[]|ArrayCollection
     */
    public function findForAccountGroups(array $accountGroups, Category $category)
    {
        $qb = $this->createQueryBuilder('v');

        $visibilities = $qb
            ->where($qb->expr()->in('v.accountGroup', ':accountGroups'))
            ->andWhere('v.category = :category')
            ->setParameter('accountGroups', $accountGroups)
            ->setParameter('category', $category)
            ->getQuery()
            ->execute();

        return new ArrayCollection($visibilities);
    }
}
