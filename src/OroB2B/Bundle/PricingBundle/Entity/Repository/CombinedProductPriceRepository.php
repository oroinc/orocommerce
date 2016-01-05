<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class CombinedProductPriceRepository extends ProductPriceRepository
{
    /**
     * @param InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
     * @param CombinedPriceList $combinedPriceList
     * @param PriceList $priceList
     * @param Product $product
     * @param boolean $mergeAllowed
     */
    public function insertPricesByPriceListForProduct(
        InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor,
        CombinedPriceList $combinedPriceList,
        PriceList $priceList,
        Product $product,
        $mergeAllowed
    ) {
        $qb = $this->getEntityManager()
            ->getRepository('OroB2BPricingBundle:ProductPrice')
            ->createQueryBuilder('pp');
        $qb->select(
            'IDENTITY(pp.product)',
            'IDENTITY(pp.unit)',
            (string)$qb->expr()->literal($combinedPriceList->getId()),
            'pp.productSku',
            'pp.quantity',
            'pp.value',
            'pp.currency',
            sprintf('CAST(%d as boolean)', (int)$mergeAllowed)
        )
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('pp.priceList', ':currentPriceList'),
                    $qb->expr()->eq('pp.product', ':currentProduct')
                )
            )
            ->groupBy('pp.id')
            ->setParameter('currentPriceList', $priceList)
            ->setParameter('currentProduct', $product);

        $this->addUniquePriceCondition($qb, $combinedPriceList, $mergeAllowed);

        $insertFromSelectQueryExecutor->execute(
            'OroB2BPricingBundle:CombinedProductPrice',
            [
                'product',
                'unit',
                'priceList',
                'productSku',
                'quantity',
                'value',
                'currency',
                'mergeAllowed'
            ],
            $qb
        );
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param Product $product
     */
    public function deletePricesByProduct(CombinedPriceList $combinedPriceList, Product $product)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete($this->getEntityName(), 'combinedPrice')
            ->where($qb->expr()->eq('combinedPrice.priceList', ':combinedPriceList'))
            ->andWhere($qb->expr()->eq('combinedPrice.product', ':product'))
            ->setParameter('combinedPriceList', $combinedPriceList)
            ->setParameter('product', $product)
            ->getQuery()
            ->execute();
    }

    /**
     * @param QueryBuilder $qb
     * @param CombinedPriceList $combinedPriceList
     * @param boolean $mergeAllowed
     */
    protected function addUniquePriceCondition(QueryBuilder $qb, CombinedPriceList $combinedPriceList, $mergeAllowed)
    {
        if ($mergeAllowed) {
            $qb->leftJoin(
                'OroB2BPricingBundle:CombinedProductPrice',
                'cpp',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('cpp', ':combinedPriceList'),
                    $qb->expr()->eq('pp.product', 'cpp.product')
                )
            );
        } else {
            $qb->leftJoin(
                'OroB2BPricingBundle:CombinedProductPrice',
                'cpp',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('cpp', ':combinedPriceList'),
                    $qb->expr()->eq('pp.product', 'cpp.product'),
                    $qb->expr()->eq('cpp.mergeAllowed', ':mergeAllowed')
                )
            )
                ->setParameter('mergeAllowed', false)
                ->leftJoin(
                    'OroB2BPricingBundle:CombinedProductPrice',
                    'cpp2',
                    Join::WITH,
                    $qb->expr()->andX(
                        $qb->expr()->eq('cpp2', ':combinedPriceList'),
                        $qb->expr()->eq('pp.product', 'cpp2.product'),
                        $qb->expr()->eq('pp.unit', 'cpp2.unit'),
                        $qb->expr()->eq('pp.quantity', 'cpp2.quantity'),
                        $qb->expr()->eq('pp.currency', 'cpp2.currency')
                    )
                )
                ->andWhere(
                    $qb->expr()->isNull('cpp.id'),
                    $qb->expr()->isNull('cpp2.id')
                );
        }
        $qb->setParameter('combinedPriceList', $combinedPriceList);
    }
}
