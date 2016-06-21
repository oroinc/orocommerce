<?php

namespace OroB2B\Bundle\SaleBundle\Datagrid\CheckoutItemsCounters;

use Doctrine\ORM\EntityManagerInterface;
use OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutItemsCounters\CheckoutItemsCounterInterface;

class QuoteProductsCounter implements CheckoutItemsCounterInterface
{
    /**
     * {@inheritdoc}
     */
    public function countItems(EntityManagerInterface $em, array $ids)
    {
        $databaseResults = $em->createQueryBuilder()
            ->select('c.id', 'count(qp.id)')
            ->from('OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout', 'c')
            ->join('OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource', 's', 'WITH', 'c.source = s')
            ->join('OroB2B\Bundle\SaleBundle\Entity\QuoteDemand', 'qd', 'WITH', 's.quoteDemand = qd')
            ->join('OroB2B\Bundle\SaleBundle\Entity\QuoteProduct', 'qp', 'WITH', 'qp.quote = qd.quote')
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
