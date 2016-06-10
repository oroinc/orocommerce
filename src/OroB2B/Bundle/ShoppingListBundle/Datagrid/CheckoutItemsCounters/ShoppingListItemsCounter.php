<?php

namespace OroB2B\Bundle\ShoppingListBundle\Datagrid\CheckoutItemsCounters;

use Doctrine\ORM\EntityManagerInterface;
use OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutItemsCounters\CheckoutItemsCounterInterface;

class ShoppingListItemsCounter implements CheckoutItemsCounterInterface
{
    /**
     * {@inheritdoc}
     */
    public function countItems(EntityManagerInterface $em, array $ids)
    {
        $databaseResults = $em->createQueryBuilder()
            ->select('c.id', 'count(l.id)')
            ->from('OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout', 'c')
            ->join('OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource', 's', 'WITH', 'c.source = s')
            ->join('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', 'sl', 'WITH', 's.shoppingList = sl')
            ->join('OroB2B\Bundle\ShoppingListBundle\Entity\LineItem', 'l', 'WITH', 'l.shoppingList = sl')
            ->groupBy('c.id')
            ->where('c.id in (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getScalarResult();

        $result = [];

        foreach ($databaseResults as $databaseResult) {
            $result[$databaseResult['id']] = $databaseResult[1];
        }

        return $result;
    }
}
