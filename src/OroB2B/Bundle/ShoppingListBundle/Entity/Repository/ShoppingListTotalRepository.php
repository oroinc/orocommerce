<?php

namespace OroB2B\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListTotalRepository extends EntityRepository
{
    /**
     * @param ShoppingList $shoppingList
     * @param string|null $currency
     */
    public function deleteTotals(ShoppingList $shoppingList, $currency = null)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->delete($this->_entityName, 'totals')
            ->where('totals.shoppingList =: shoppingList')
            ->setParameter('shoppingList', $shoppingList);
        if ($currency !== null) {
            $qb->andWhere('totals.currency = :currency')
                ->setParameter('currency', $currency);
        }
        $qb->getQuery()->execute();
    }

    /**
     * @param array $cplIds
     */
    public function invalidateByCpl(array $cplIds)
    {
        if (empty($cplIds)) {
            return;
        }
        $qb = $this->createQueryBuilder('total');
        $qb->select('total')
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
            ->where($qb->expr()->in('productPrice.priceList', $cplIds))
            ->andWhere('total.valid = :isValid')
            ->setParameter(':isValid', false);

        $iterator = new BufferedQueryResultIterator($qb->getQuery());
        $ids = [];
        $qbUpdate = $this->_em->createQueryBuilder()
            ->update($this->_entityName, 'total')
            ->set('valid', false)
            ->where($qb->expr()->in('total.id', ':ids'));
        $i = 0;
        foreach ($iterator as $id) {
            $ids[] = $id;
            if ($i % 500 === 0) {
                $qbUpdate->setParameter('ids', $ids)
                    ->getQuery()
                    ->execute();
                $ids = [];
            }
        }
        if (!empty($ids)) {
            $qbUpdate->setParameter('ids', $ids)
                ->getQuery()
                ->execute();
        }
    }
}
