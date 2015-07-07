<?php
namespace OroB2B\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListRepository extends EntityRepository
{
    /**
     * @param string[] $labels
     *
     * @return ShoppingList[]|ArrayCollection
     */
    public function findInLabels(array $labels)
    {
        $qb = $this->createQueryBuilder('shopping_list');

        $qb
            ->select('shopping_list')
            ->where($qb->expr()->in('shopping_list.label', ':labels'))
            ->setParameter('labels', $labels)
            ->getQuery()
            ->execute();
    }
}
