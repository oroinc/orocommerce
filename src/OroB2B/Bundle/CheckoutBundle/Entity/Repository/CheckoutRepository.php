<?php

namespace Oro\Bundle\CheckoutBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\SaleBundle\Entity\Quote;

class CheckoutRepository extends EntityRepository
{
    /**
     * This method is returning the count of all line items,
     * whether originated from a quote, or a shopping list,
     * per Checkout.
     *
     * @param array $checkoutIds
     * @return array
     */
    public function countItemsPerCheckout(array $checkoutIds)
    {
        $databaseResults = $this->createQueryBuilder('c')
            ->select('c.id as id')
            ->addSelect('COALESCE(count(l.id) + count(qp.id), 0) as itemsCount')
            ->leftJoin('Oro\Bundle\CheckoutBundle\Entity\CheckoutSource', 's', 'WITH', 'c.source = s')
            ->leftJoin('Oro\Bundle\ShoppingListBundle\Entity\ShoppingList', 'sl', 'WITH', 's.shoppingList = sl')
            ->leftJoin('Oro\Bundle\ShoppingListBundle\Entity\LineItem', 'l', 'WITH', 'l.shoppingList = sl')
            ->leftJoin('Oro\Bundle\SaleBundle\Entity\QuoteDemand', 'qd', 'WITH', 's.quoteDemand = qd')
            ->leftJoin('Oro\Bundle\SaleBundle\Entity\QuoteProduct', 'qp', 'WITH', 'qp.quote = qd.quote')
            ->groupBy('c.id')
            ->where('c.id in (:ids)')
            ->setParameter('ids', $checkoutIds)
            ->getQuery()
            ->getScalarResult();
        
        return $this->extractCheckoutItemsCounts($databaseResults);
    }

    /**
     * Returning the source information of the checkouts.
     *
     * @param array $checkoutIds
     * @return array
     */
    public function getSourcePerCheckout(array $checkoutIds)
    {
        $databaseResults = $this->createQueryBuilder('c')
            ->select('c.id as id')
            ->addSelect('qd as quote')
            ->addSelect('sl as shoppingList')
            ->leftJoin('Oro\Bundle\CheckoutBundle\Entity\CheckoutSource', 's', 'WITH', 'c.source = s')
            ->leftJoin('Oro\Bundle\ShoppingListBundle\Entity\ShoppingList', 'sl', 'WITH', 's.shoppingList = sl')
            ->leftJoin('Oro\Bundle\SaleBundle\Entity\QuoteDemand', 'qd', 'WITH', 's.quoteDemand = qd')
            ->leftJoin('Oro\Bundle\SaleBundle\Entity\Quote', 'q', 'WITH', 'qd.quote = q')
            ->where('c.id in (:ids)')
            ->setParameter('ids', $checkoutIds)
            ->getQuery()
            ->getResult();

        return $this->extractShoppingListQuoteSources($databaseResults);
    }

    /**
     * Cutting out ID and ITEMSCOUNT columns from the query
     * and making an associative array out of it.
     *
     * @param $results
     * @return array
     */
    private function extractCheckoutItemsCounts($results)
    {
        $result = [];

        if (!count($results)) {
            return $result;
        }

        $ids        = array_column($results, 'id');
        $itemCounts = array_column($results, 'itemsCount');

        $result = array_combine(
            $ids,
            $itemCounts
        );
        
        return $result;
    }
    
    /**
     * Collecting Quote and ShoppingList objects from the query result
     * and integrating into one dataset, indexed by Checkout ID.
     *
     * @param $results
     * @return array
     */
    private function extractShoppingListQuoteSources($results)
    {
        if (!count($results)) {
            return [];
        }
        
        $quotes         = array_column($results, 'quote');
        $shoppingLists  = array_column($results, 'shoppingList');
        $ids            = array_column($results, 'id');

        // we will overwrite one array with another
        // thus get rid of nulls, as they should not overwrite real values
        $quotes         = array_filter($quotes);
        
        $integrated     = array_replace($shoppingLists, $quotes);

        $result         = array_combine(
            $ids,
            $integrated
        );

        return $result;
    }

    /**
     * @param Quote $quote
     * @return Checkout
     */
    public function getCheckoutByQuote(Quote $quote)
    {
        $qb = $this->createQueryBuilder('checkout');

        return $qb->addSelect(['source', 'qd', 'quote'])
            ->innerJoin('checkout.source', 'source')
            ->innerJoin('source.quoteDemand', 'qd')
            ->innerJoin('qd.quote', 'quote')
            ->where(
                $qb->expr()->eq('quote', ':quote')
            )
            ->setParameter('quote', $quote)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
