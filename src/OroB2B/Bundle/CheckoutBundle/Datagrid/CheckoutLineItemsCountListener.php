<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * This listener counts items in orders and adds the result to the grid data.
 * It either counts items from shopping list which order was created from, or the quote.
 *
 * Class CheckoutLineItemsCountListener
 * @package OroB2B\Bundle\CheckoutBundle\Datagrid
 */
class CheckoutLineItemsCountListener
{
    /**
     * @var RegistryInterface
     */
    protected $doctrine;

    /**
     * @param RegistryInterface $doctrine
     */
    public function __construct(
        RegistryInterface $doctrine
    ) {
        $this->doctrine = $doctrine;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        $em = $this->doctrine->getEntityManagerForClass(
            'OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout'
        );

        $ids = [];

        foreach ($records as $record) {
            $ids[] = $record->getValue('id');
        }

        $shoppingListsItemsCounts = $this->countShoppingListItems($em, $ids);

        $quoteProductCounts = $this->countQuoteProducts($em, $ids);

        foreach ($records as $record) {
            $totalItems = 0;

            foreach ($shoppingListsItemsCounts as $shoppingListsItemsCount) {
                if ($shoppingListsItemsCount['id'] == $record->getValue('id')) {
                    $totalItems = $shoppingListsItemsCount[1];
                }
            }

            foreach ($quoteProductCounts as $quoteProductCount) {
                if ($quoteProductCount['id'] == $record->getValue('id')) {
                    $totalItems = $quoteProductCount[1];
                }
            }

            $record->addData(['itemsCount' => $totalItems]);
        }
    }

    /**
     * @param EntityManagerInterface $em
     * @param array $ids
     * @return array
     */
    private function countShoppingListItems(EntityManagerInterface $em, array $ids)
    {
         return $em->createQueryBuilder()
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
    }

    /**
     * @param EntityManagerInterface $em
     * @param array $ids
     * @return array
     */
    private function countQuoteProducts(EntityManagerInterface $em, array $ids)
    {
        return $em->createQueryBuilder()
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
    }
}
