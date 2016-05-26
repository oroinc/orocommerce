<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\Query;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class MinimalProductPriceRepository extends ProductPriceRepository
{
    /**
     * @param InsertFromSelectQueryExecutor $insertQueryExecutor
     * @param CombinedPriceList $cpl
     * @param Product|null $product
     */
    public function updateMinimalPrices(
        InsertFromSelectQueryExecutor $insertQueryExecutor,
        CombinedPriceList $cpl,
        Product $product = null
    ) {
        $this->deleteByPriceList($cpl, $product);

        $qb = $this->_em->createQueryBuilder();
        $qb
            ->select(
                'IDENTITY(productPrice.product)',
                'IDENTITY(productPrice.priceList)',
                'MIN(IDENTITY(productPrice.unit))',
                'MIN(productPrice.productSku)',
                'MIN(productPrice.quantity)',
                'MIN(productPrice.value)',
                'MIN(productPrice.currency)'
            )
            ->from('OroB2BPricingBundle:CombinedProductPrice', 'productPrice')
            ->where($qb->expr()->eq('productPrice.priceList', ':sourcePriceList'))
            ->groupBy('productPrice.product, productPrice.priceList, productPrice.currency')
            ->setParameter('sourcePriceList', $cpl);


        if ($product) {
            $qb->andWhere($qb->expr()->eq('productPrice.product', ':product'))
                ->setParameter('product', $product);
        }

        $qb->andWhere(
            $qb->expr()->not(
                $qb->expr()->exists($this->getSubQueryOfLowerPrices('productPrice')->getDQL())
            )
        );

        $fields = [
            'product',
            'priceList',
            'unit',
            'productSku',
            'quantity',
            'value',
            'currency',
        ];

        $insertQueryExecutor->execute($this->getClassName(), $fields, $qb);
    }

    /**
     * @param string $rooAlias
     * @return Query
     */
    protected function getSubQueryOfLowerPrices($rooAlias)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('low_price.id')
            ->from('OroB2BPricingBundle:CombinedProductPrice', 'low_price')
            ->where($qb->expr()->eq('low_price.priceList', $rooAlias . '.priceList'))
            ->andWhere($qb->expr()->eq('low_price.product', $rooAlias . '.product'))
            ->andWhere($qb->expr()->eq('low_price.currency', $rooAlias . '.currency'))
            ->andWhere($qb->expr()->lt('low_price.value', $rooAlias . '.value'));

        return $qb->getQuery();
    }
}
