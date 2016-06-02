<?php

namespace OroB2B\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

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
            ->where($qb->expr()->in('productPrice.priceList', $cplIds))
            ->andWhere('total.valid = :isValid')
            ->setParameter(':isValid', true);

        $iterator = new BufferedQueryResultIterator($qb->getQuery());
        $iterator->setHydrationMode(Query::HYDRATE_SCALAR);
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
                $qbUpdate->where($qb->expr()->in('total.id', $ids))
                    ->getQuery()
                    ->execute();
                $ids = [];
            }
        }
        if (!empty($ids)) {
            $qbUpdate->where($qb->expr()->in('total.id', $ids))
                ->getQuery()
                ->execute();
        }
    }
}
