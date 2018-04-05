<?php

namespace Oro\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;

class ShoppingListTotalRepository extends EntityRepository
{
    /**
     * Invalidate ShoppingList subtotals by given Combined Price List ids
     *
     * @param array $combinedPriceListIds
     */
    public function invalidateByCombinedPriceList(array $combinedPriceListIds)
    {
        if (!$combinedPriceListIds) {
            return;
        }

        $subQuery = $this->getEntityManager()->createQueryBuilder();
        $subQuery->select('1')
            ->from('OroShoppingListBundle:LineItem', 'lineItem')
            ->join(
                'OroPricingBundle:CombinedProductPrice',
                'productPrice',
                Join::WITH,
                $subQuery->expr()->eq('lineItem.product', 'productPrice.product')
            )
            ->where(
                $subQuery->expr()->eq('total.shoppingList', 'lineItem.shoppingList'),
                $subQuery->expr()->in('productPrice.priceList', ':priceLists')
            );

        $qb = $this->createQueryBuilder('total');
        $qb->select('total.id')
            ->where(
                $qb->expr()->eq('total.valid', ':isValid'),
                $qb->expr()->exists($subQuery)
            )
            ->setParameter('priceLists', $combinedPriceListIds)
            ->setParameter('isValid', true);

        $iterator = new BufferedIdentityQueryResultIterator($qb);
        $iterator->setHydrationMode(Query::HYDRATE_SCALAR);
        $this->invalidateTotals($iterator);
    }

    /**
     * @param array $customerIds
     * @param int $websiteId
     */
    public function invalidateByCustomers(array $customerIds, $websiteId)
    {
        if (empty($customerIds)) {
            return;
        }
        $qb = $this->getBaseInvalidateQb($websiteId);
        $qb->andWhere($qb->expr()->in('shoppingList.customer', ':customers'))
            ->setParameter('customers', $customerIds);

        $iterator = new BufferedIdentityQueryResultIterator($qb);
        $iterator->setHydrationMode(Query::HYDRATE_SCALAR);
        $this->invalidateTotals($iterator);
    }

    /**
     * @param int $websiteId
     */
    public function invalidateGuestShoppingLists($websiteId)
    {
        $qb = $this->getBaseInvalidateQb($websiteId);
        $qb->join('shoppingList.visitors', 'visitor');

        $iterator = new BufferedIdentityQueryResultIterator($qb);
        $iterator->setHydrationMode(Query::HYDRATE_SCALAR);
        $this->invalidateTotals($iterator);
    }

    /**
     * @param int $websiteId
     * @return QueryBuilder
     */
    protected function getBaseInvalidateQb($websiteId)
    {
        $qb = $this->createQueryBuilder('total');
        $qb->select('DISTINCT total.id')
            ->join('total.shoppingList', 'shoppingList')
            ->andWhere($qb->expr()->eq('shoppingList.website', ':website'))
            ->andWhere($qb->expr()->eq('total.valid', ':isValid'))
            ->setParameter('website', $websiteId)
            ->setParameter('isValid', true);

        return $qb;
    }

    /**
     * @param \Iterator $iterator
     */
    protected function invalidateTotals(\Iterator $iterator)
    {
        $ids = [];
        $qbUpdate = $this->_em->createQueryBuilder();
        $qbUpdate->update($this->_entityName, 'total')
            ->where($qbUpdate->expr()->in('total.id', ':totalIds'))
            ->set('total.valid', ':valid')
            ->setParameter('valid', false);
        $i = 0;
        foreach ($iterator as $total) {
            $ids[] = $total['id'];
            $i++;
            if ($i % 500 === 0) {
                $qbUpdate->setParameter('totalIds', $ids)
                    ->getQuery()
                    ->execute();
                $ids = [];
            }
        }
        if (!empty($ids)) {
            $qbUpdate->setParameter('totalIds', $ids)
                ->getQuery()
                ->execute();
        }
    }
}
