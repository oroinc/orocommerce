<?php

namespace OroB2B\Bundle\CheckoutBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class BaseCheckoutRepository extends EntityRepository
{
    /**
     * @param array $ids
     * @return array
     */
    public function countItemsByIds(array $ids)
    {
        $databaseResults = $this->createQueryBuilder('c')
            ->select('c.id as id')
            ->addSelect('COALESCE(count(l.id) + count(qp.id), 0) as itemsCount')
            ->leftJoin('OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource', 's', 'WITH', 'c.source = s')
            ->leftJoin('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', 'sl', 'WITH', 's.shoppingList = sl')
            ->leftJoin('OroB2B\Bundle\ShoppingListBundle\Entity\LineItem', 'l', 'WITH', 'l.shoppingList = sl')
            ->leftJoin('OroB2B\Bundle\SaleBundle\Entity\QuoteDemand', 'qd', 'WITH', 's.quoteDemand = qd')
            ->leftJoin('OroB2B\Bundle\SaleBundle\Entity\QuoteProduct', 'qp', 'WITH', 'qp.quote = qd.quote')
            ->groupBy('c.id')
            ->where('c.id in (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getScalarResult();

        $result = [];

        foreach ($databaseResults as $databaseResult) {
            $result[$databaseResult['id']] = $databaseResult['itemsCount'];
        }

        return $result;
    }

    /**
     * @param array $ids
     * @return array
     */
    public function findSourceByIds(array $ids)
    {
        $databaseResults = $this->createQueryBuilder('c')
            ->select('c.id as id')
            ->addSelect('q as quote')
            ->addSelect('sl as shoppingList')
            ->join('OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource', 's', 'WITH', 'c.source = s')

            ->leftJoin('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', 'sl', 'WITH', 's.shoppingList = sl')

            ->leftJoin('OroB2B\Bundle\SaleBundle\Entity\QuoteDemand', 'qd', 'WITH', 's.quoteDemand = qd')
            ->leftJoin('OroB2B\Bundle\SaleBundle\Entity\Quote', 'q', 'WITH', 'qd.quote = q')

            ->where('c.id in (:ids)')
            ->groupBy('c.id')
            ->where('c.id in (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $id = null;
        $result = [];

        foreach ($databaseResults as $databaseResult) {
            if (isset($databaseResult['id']) && $databaseResult['id']) {
                $id = $databaseResult['id'];

                if (isset($shoppingListToInsert) && !isset($result[$id])) {
                    $result[$id] = $shoppingListToInsert;

                    unset($shoppingListToInsert);

                    continue;
                }
            }

            if (isset($databaseResult['quote']) && $databaseResult['quote'] !== null && $id) {
                $result[$id] = $databaseResult['quote'];

                continue;
            }

            if (isset($databaseResult['shoppingList']) && $databaseResult['shoppingList'] !== null && $id) {
                $result[$id] = $databaseResult['shoppingList'];

                continue;
            }

            if (isset($databaseResult['shoppingList']) && $databaseResult['shoppingList'] !== null) {
                $shoppingListToInsert = $databaseResult['shoppingList'];

                continue;
            }
        }

        return $result;
    }
}
