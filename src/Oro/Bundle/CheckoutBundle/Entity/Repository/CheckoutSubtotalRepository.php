<?php

namespace Oro\Bundle\CheckoutBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Component\DoctrineUtils\ORM\ResultSetMappingUtil;
use Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder;

/**
 * Repository for CheckoutSubtotal entity
 */
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

        $expr = $this->getEntityManager()->getExpressionBuilder();

        $rsm = ResultSetMappingUtil::createResultSetMapping(
            $this->getEntityManager()->getConnection()->getDatabasePlatform()
        );

        $updateQB = new SqlQueryBuilder($this->getEntityManager(), $rsm);

        $updateQB->update('oro_checkout_subtotal', 'cs')
            ->innerJoin('cs', 'oro_checkout', 'c', $expr->eq('cs.checkout_id', 'c.id'))
            ->set('is_valid', ':newIsValid')
            ->where(
                $expr->andX(
                    $expr->in('cs.combined_price_list_id', ':combinedPriceListIds'),
                    $expr->eq('cs.is_valid', ':isValid'),
                    $expr->eq('c.deleted', ':isDeleted'),
                    $expr->eq('c.completed', ':isCompleted')
                )
            );

        $updateQB->getQuery()->execute([
            'newIsValid'           => false,
            'isValid'              => true,
            'combinedPriceListIds' => $combinedPriceListIds,
            'isDeleted'            => false,
            'isCompleted'          => false
        ]);
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

        $expr = $this->getEntityManager()->getExpressionBuilder();

        $lineItemSubQB = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $lineItemSubQB->select($expr->literal(1))
            ->from('oro_checkout_line_item', 'li')
            ->where($expr->eq('li.checkout_id', 'c.id'))
            ->andWhere($expr->eq('li.is_price_fixed', ':isFixed'));

        $rsm = ResultSetMappingUtil::createResultSetMapping(
            $this->getEntityManager()->getConnection()->getDatabasePlatform()
        );
        $updateQB = new SqlQueryBuilder($this->getEntityManager(), $rsm);
        $updateQB->update('oro_checkout_subtotal', 'cs')
            ->innerJoin('cs', 'oro_checkout', 'c', $expr->eq('cs.checkout_id', 'c.id'))
            ->set('is_valid', ':newIsValid')
            ->where(
                $expr->andX(
                    $expr->eq('cs.is_valid', ':isValid'),
                    $expr->eq('c.website_id', ':websiteId'),
                    $expr->in('c.customer_id', ':customerIds'),
                    $expr->eq('c.deleted', ':isDeleted'),
                    $expr->eq('c.completed', ':isCompleted'),
                    $expr->exists($lineItemSubQB->getSQL())
                )
            );

        $updateQB->getQuery()->execute([
            'newIsValid' => false,
            'isValid' => true,
            'websiteId' => $websiteId,
            'customerIds' => $customerIds,
            'isFixed' => false,
            'isDeleted' => false,
            'isCompleted' => false,
        ]);
    }
}
