<?php

namespace Oro\Bundle\CheckoutBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;

class CheckoutSubtotalRepository extends EntityRepository
{
    /**
     * Invalidate checkout subtotals by given Combined Price List ids
     *
     * @param array $combinedPriceListIds
     */
    public function invalidateByCombinedPriceList(array $combinedPriceListIds)
    {
        if (!$combinedPriceListIds) {
            return;
        }

        $qb = $this->createQueryBuilder('cs');
        $qb->select('cs.id')
            ->where(
                $qb->expr()->in('cs.combinedPriceList', ':priceLists'),
                $qb->expr()->eq('cs.valid', ':isValid')
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
        $qb->andWhere($qb->expr()->in('c.customer', ':customers'))
            ->setParameter('customers', $customerIds);
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
        $qb = $this->createQueryBuilder('cs');
        $qb->select('DISTINCT cs.id')
            ->join('cs.checkout', 'c')
            ->join('c.lineItems', 'cli')
            ->andWhere($qb->expr()->eq('c.website', ':website'))
            ->andWhere($qb->expr()->eq('cs.valid', ':isValid'))
            ->andWhere($qb->expr()->eq('cli.priceFixed', ':isPriceFixed'))
            ->setParameter('website', $websiteId)
            ->setParameter('isValid', true)
            ->setParameter('isPriceFixed', false);

        return $qb;
    }

    /**
     * @param \Iterator $iterator
     */
    protected function invalidateTotals(\Iterator $iterator)
    {
        $ids = [];
        $qbUpdate = $this->_em->createQueryBuilder();
        $qbUpdate->update($this->_entityName, 'cs')
            ->where($qbUpdate->expr()->in('cs.id', ':subtotalIds'))
            ->set('cs.valid', ':valid')
            ->setParameter('valid', false);
        $i = 0;
        foreach ($iterator as $subtotal) {
            $ids[] = $subtotal['id'];
            $i++;
            if ($i % 500 === 0) {
                $qbUpdate->setParameter('subtotalIds', $ids)
                    ->getQuery()
                    ->execute();
                $ids = [];
            }
        }
        if (!empty($ids)) {
            $qbUpdate->setParameter('subtotalIds', $ids)
                ->getQuery()
                ->execute();
        }
    }
}
