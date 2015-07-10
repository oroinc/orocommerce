<?php
namespace OroB2B\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;

class ShoppingListRepository extends EntityRepository
{
    /**
     * @param AccountUser $accountUser
     *
     * @return array
     */
    public function findCurrentForAccountUser(AccountUser $accountUser)
    {
        return $this->createQueryBuilder('list')
            ->select('list')
            ->where('list.accountUser = :accountUser')
            ->andWhere('list.isCurrent = 1')
            ->setParameter('accountUser', $accountUser)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string[] $labels
     *
     * @return ShoppingList[]|ArrayCollection
     */
    public function findInLabels(array $labels)
    {
        $qb = $this->createQueryBuilder('shopping_list');

        return $qb
            ->select('shopping_list')
            ->where($qb->expr()->in('shopping_list.label', ':labels'))
            ->setParameter('labels', $labels)
            ->getQuery()
            ->execute();
    }
}
