<?php

namespace OroB2B\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;

class LineItemRepository extends EntityRepository
{
    /**
     * Find line item with the same product and unit
     *
     * @param LineItem $lineItem
     *
     * @return LineItem
     */
    public function findDuplicate(LineItem $lineItem)
    {
        $qb = $this->createQueryBuilder('li')
            ->where('li.product = :product')
            ->andWhere('li.unit = :unit')
            ->andWhere('li.shoppingList = :shoppingList')
            ->setParameter('product', $lineItem->getProduct())
            ->setParameter('unit', $lineItem->getUnit())
            ->setParameter('shoppingList', $lineItem->getShoppingList());

        if ($lineItem->getId()) {
            $qb
                ->andWhere('li.id != :currentId')
                ->setParameter('currentId', $lineItem->getId());
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
