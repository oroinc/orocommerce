<?php

namespace OroB2B\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

class ShoppingListTotalRepository extends EntityRepository
{
    /**
     * @param array $cplIds
     */
    public function invalidateByCpl(array $cplIds)
    {
        if (empty($cplIds)) {
            return;
        }
        $qb = $this->createQueryBuilder('total');
        $qb->select('DISTINCT total.id')
            ->join(
                'OroB2BShoppingListBundle:LineItem',
                'lineItem',
                Join::WITH,
                $qb->expr()->eq('total.shoppingList', 'lineItem.shoppingList')
            )
            ->join(
                'OroB2BPricingBundle:CombinedProductPrice',
                'productPrice',
                Join::WITH,
                $qb->expr()->eq('lineItem.product', 'productPrice.product')
            )
            ->where($qb->expr()->in('productPrice.priceList', ':priceLists'))
            ->andWhere('total.valid = :isValid')
            ->setParameter(':isValid', true)
            ->setParameter('priceLists', $cplIds, Type::SIMPLE_ARRAY);

        $iterator = new BufferedQueryResultIterator($qb);
        $iterator->setHydrationMode(Query::HYDRATE_SCALAR);

        $this->invalidateTotals($iterator);
    }

    /**
     * @param array $accountIds
     * @param int $websiteId
     */
    public function invalidateByAccounts(array $accountIds, $websiteId)
    {
        if (empty($accountIds)) {
            return;
        }
        $qb = $this->getBaseInvalidateQb($websiteId);
        $qb->andWhere($qb->expr()->in('shoppingList.account', ':accounts'))
            ->setParameter('accounts', $accountIds, Type::SIMPLE_ARRAY);

        $iterator = new BufferedQueryResultIterator($qb);
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
     * @param BufferedQueryResultIterator $iterator
     */
    protected function invalidateTotals(BufferedQueryResultIterator $iterator)
    {
        $ids = [];
        $qbUpdate = $this->_em->createQueryBuilder()
            ->update($this->_entityName, 'total')
            ->set('total.valid', ':valid')
            ->setParameter('valid', false);
        $i = 0;
        foreach ($iterator as $total) {
            $ids[] = $total['id'];
            $i++;
            if ($i % 500 === 0) {
                $qbUpdate->where($qbUpdate->expr()->in('total.id', $ids))
                    ->getQuery()
                    ->execute();
                $ids = [];
            }
        }
        if (!empty($ids)) {
            $qbUpdate->where($qbUpdate->expr()->in('total.id', $ids))
                ->getQuery()
                ->execute();
        }
    }
}
