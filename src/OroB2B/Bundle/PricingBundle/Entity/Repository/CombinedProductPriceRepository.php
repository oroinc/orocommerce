<?php


namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class CombinedProductPriceRepository extends ProductPriceRepository
{
    public function insertPrices(
        CombinedPriceListToPriceList $combinedPriceListToPriceList,
        InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
    ) {
        if ($combinedPriceListToPriceList->isMergeAllowed()) {
            $qb = $this->getPricesByPriceListOnMergeAllowed(
                $combinedPriceListToPriceList->getCombinedPriceList(),
                $combinedPriceListToPriceList->getPriceList()
            );
        } else {
            $qb = $this->getPricesByPriceListOnMergeNotAllowed(
                $combinedPriceListToPriceList->getCombinedPriceList(),
                $combinedPriceListToPriceList->getPriceList()
            );
        }
        $insertFromSelectQueryExecutor->execute(
            $this->getClassName(),
            ['unit', 'product', 'productSku', 'quantity', 'value', 'currency', 'merge'],
            $qb
        );
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param PriceList $priceList
     * @return QueryBuilder
     */
    public function getPricesByPriceListOnMergeAllowed(
        CombinedPriceList $combinedPriceList,
        PriceList $priceList
    ) {
        $qb = $this->getEntityManager()
            ->getRepository('OroB2BPricingBundle:ProductPrice')
            ->createQueryBuilder('pp');
        $qb->select(
            'IDENTITY(pp.unit) as unitId',
            'IDENTITY(pp.product) as productId',
            $qb->expr()->literal($combinedPriceList->getId()) . ' AS combinedPriceList',
            'pp.productSku',
            'pp.quantity',
            'pp.value',
            'pp.currency',
            $qb->expr()->literal(1) . ' AS merge'
        )
            ->leftJoin(
                'OroB2BPricingBundle:CombinedProductPrice',
                'cpp',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('cpp', ':comb_pl'),
                    $qb->expr()->eq('pp.product', 'cpp.product'),
                    $qb->expr()->eq('cpp.merge', ':falseAlias')
                )
            )
            ->leftJoin(
                'OroB2BPricingBundle:CombinedProductPrice',
                'cpp2',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('cpp2', ':comb_pl'),
                    $qb->expr()->eq('pp.product', 'cpp2.product'),
                    $qb->expr()->eq('pp.unit', 'cpp2.unit'),
                    $qb->expr()->eq('pp.quantity', 'cpp2.quantity'),
                    $qb->expr()->eq('pp.currency', 'cpp2.currency')
                )
            )
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('pp.priceList', ':price_list'),
                    $qb->expr()->isNull('cpp.id'),
                    $qb->expr()->isNull('cpp2.id')
                )
            )
            ->setParameter('price_list', $priceList)
            ->setParameter('comb_pl', $combinedPriceList)
            ->setParameter('falseAlias', false);

        return $qb;
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param PriceList $priceList
     * @return QueryBuilder
     */
    public function getPricesByPriceListOnMergeNotAllowed(
        CombinedPriceList $combinedPriceList,
        PriceList $priceList
    ) {
        $qb = $this->getEntityManager()
            ->getRepository('OroB2BPricingBundle:ProductPrice')
            ->createQueryBuilder('pp');
        $qb->select(
            'IDENTITY(pp.unit) as unitId',
            'IDENTITY(pp.product) as productId',
            $qb->expr()->literal($combinedPriceList->getId()) . ' AS combinedPriceList',
            'pp.productSku',
            'pp.quantity',
            'pp.value',
            'pp.currency',
            $qb->expr()->literal(0) . ' AS merge'
        )
            ->leftJoin(
                'OroB2BPricingBundle:CombinedProductPrice',
                'cpp',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('cpp', ':comb_pl'),
                    $qb->expr()->eq('pp.product', 'cpp.product')
                )
            )
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('pp.priceList', ':price_list'),
                    $qb->expr()->isNull('cpp.id')
                )
            )
            ->setParameter('price_list', $priceList)
            ->setParameter('comb_pl', $combinedPriceList);

        return $qb;
    }
}
