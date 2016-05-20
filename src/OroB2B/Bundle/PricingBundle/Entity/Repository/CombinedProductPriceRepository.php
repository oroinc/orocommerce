<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class CombinedProductPriceRepository extends ProductPriceRepository
{
    /**
     * @param InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
     * @param CombinedPriceList $combinedPriceList
     * @param PriceList $priceList
     * @param boolean $mergeAllowed
     * @param Product|null $product
     */
    public function insertPricesByPriceList(
        InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor,
        CombinedPriceList $combinedPriceList,
        PriceList $priceList,
        $mergeAllowed,
        Product $product = null
    ) {
        $qb = $this->getEntityManager()
            ->getRepository('OroB2BPricingBundle:ProductPrice')
            ->createQueryBuilder('pp');

        $qb
            ->select(
                'IDENTITY(pp.product)',
                'IDENTITY(pp.unit)',
                (string)$qb->expr()->literal($combinedPriceList->getId()),
                'pp.productSku',
                'pp.quantity',
                'pp.value',
                'pp.currency',
                sprintf('CAST(%d as boolean)', (int)$mergeAllowed)
            )
            ->where($qb->expr()->eq('pp.priceList', ':currentPriceList'))
            ->groupBy('pp.id')
            ->setParameter('currentPriceList', $priceList);

        if ($product) {
            $qb->andWhere($qb->expr()->eq('pp.product', ':currentProduct'))
                ->setParameter('currentProduct', $product);
        }

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
     * @param Product|null $product
     */
    public function deleteCombinedPrices(CombinedPriceList $combinedPriceList, Product $product = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete($this->getEntityName(), 'combinedPrice')
            ->where($qb->expr()->eq('combinedPrice.priceList', ':combinedPriceList'))
            ->setParameter('combinedPriceList', $combinedPriceList);

        if ($product) {
            $qb->andWhere($qb->expr()->eq('combinedPrice.product', ':product'))
                ->setParameter('product', $product);
        }

        $qb->getQuery()->execute();
    }

    /**
     * @param CombinedPriceList $priceList
     * @param array $productIds
     * @param null|string $currency
     * @return CombinedProductPrice[]
     */
    public function getPricesForProductsByPriceList(CombinedPriceList $priceList, array $productIds, $currency = null)
    {
        if (count($productIds) === 0) {
            return [];
        }

        $qb = $this->createQueryBuilder('cpp');

        $qb->select('cpp')
            ->where($qb->expr()->eq('cpp.priceList', ':priceList'))
            ->andWhere($qb->expr()->in('cpp.product', ':productIds'))
            ->setParameters([
                'priceList' => $priceList,
                'productIds' => $productIds
            ]);

        if ($currency) {
            $qb->andWhere($qb->expr()->eq('cpp.currency', ':currency'))
                ->setParameter('currency', $currency);
        }

        return $qb->getQuery()->getResult();
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
                    $qb->expr()->eq('cpp.priceList', ':combinedPriceList'),
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
                    $qb->expr()->eq('cpp2.priceList', ':combinedPriceList'),
                    $qb->expr()->eq('pp.product', 'cpp2.product'),
                    $qb->expr()->eq('pp.unit', 'cpp2.unit'),
                    $qb->expr()->eq('pp.quantity', 'cpp2.quantity'),
                    $qb->expr()->eq('pp.currency', 'cpp2.currency')
                )
            )
            ->andWhere($qb->expr()->isNull('cpp2.id'));
        } else {
            $qb->leftJoin(
                'OroB2BPricingBundle:CombinedProductPrice',
                'cpp',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('cpp.priceList', ':combinedPriceList'),
                    $qb->expr()->eq('pp.product', 'cpp.product')
                )
            );
        }

        $qb->andWhere($qb->expr()->isNull('cpp.id'))
            ->setParameter('combinedPriceList', $combinedPriceList->getId());
    }
}
