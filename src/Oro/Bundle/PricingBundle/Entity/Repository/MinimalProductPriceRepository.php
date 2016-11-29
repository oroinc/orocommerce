<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\Query;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToAccount;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToAccountGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\MinimalProductPrice;
use Oro\Bundle\ProductBundle\Entity\Product;

class MinimalProductPriceRepository extends BaseProductPriceRepository
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
            ->from('OroPricingBundle:CombinedProductPrice', 'productPrice')
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
            ->from('OroPricingBundle:CombinedProductPrice', 'low_price')
            ->where($qb->expr()->eq('low_price.priceList', $rooAlias.'.priceList'))
            ->andWhere($qb->expr()->eq('low_price.product', $rooAlias.'.product'))
            ->andWhere($qb->expr()->eq('low_price.currency', $rooAlias.'.currency'))
            ->andWhere($qb->expr()->lt('low_price.value', $rooAlias.'.value'));

        return $qb->getQuery();
    }

    /**
     * @param integer $websiteId
     * @param Product[] $products
     * @param CombinedPriceList $configCpl
     * @return MinimalProductPrice[]
     */
    public function findByWebsite($websiteId, array $products, $configCpl)
    {
        $qb = $this->createQueryBuilder('mp');
        $qb->select(
            'IDENTITY(mp.product) as product',
            'mp.value',
            'mp.currency',
            'IDENTITY(mp.unit) as unit',
            'IDENTITY(mp.priceList) as cpl'
        )
            ->where('mp.product in (:products)')
            ->setParameter('products', $products)
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->orX(
                        $qb->expr()->exists(
                            $this->getEntityManager()
                                ->createQueryBuilder()
                                ->from(CombinedPriceListToWebsite::class, 'cpl_w')
                                ->select('cpl_w.id')
                                ->where('cpl_w.website = :websiteId')
                                ->andWhere('cpl_w.priceList = mp.priceList')
                                ->getDQL()
                        ),
                        $qb->expr()->exists(
                            $this->getEntityManager()
                                ->createQueryBuilder()
                                ->from(CombinedPriceListToAccount::class, 'cpl_a')
                                ->select('cpl_a.id')
                                ->where('cpl_a.website = :websiteId')
                                ->andWhere('cpl_a.priceList = mp.priceList')
                                ->getDQL()
                        ),
                        $qb->expr()->exists(
                            $this->getEntityManager()
                                ->createQueryBuilder()
                                ->from(CombinedPriceListToAccountGroup::class, 'cpl_ag')
                                ->select('cpl_ag.id')
                                ->where('cpl_ag.website = :websiteId')
                                ->andWhere('cpl_ag.priceList = mp.priceList')
                                ->getDQL()
                        )
                    ),
                    'mp.priceList = :conf_cpl'
                )
            )
            ->setParameter('websiteId', $websiteId)
            ->setParameter('conf_cpl', $configCpl);

        return $qb->getQuery()->getArrayResult();
    }
}
