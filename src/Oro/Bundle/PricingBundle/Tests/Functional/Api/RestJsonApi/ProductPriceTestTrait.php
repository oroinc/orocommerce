<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardOutputResultModifier;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

trait ProductPriceTestTrait
{
    private function findProductPriceByUniqueKey(
        int $quantity,
        string $currency,
        PriceList $priceList,
        Product $product,
        ProductUnit $unit
    ): ?ProductPrice {
        $queryBuilder = $this->getEntityManager()
            ->getRepository(ProductPrice::class)
            ->createQueryBuilder('price');

        $queryBuilder
            ->andWhere('price.quantity = :quantity')
            ->andWhere('price.currency = :currency')
            ->andWhere('price.priceList = :priceList')
            ->andWhere('price.product = :product')
            ->andWhere('price.unit = :unit')
            ->setParameter('quantity', $quantity)
            ->setParameter('currency', $currency)
            ->setParameter('priceList', $priceList)
            ->setParameter('product', $product)
            ->setParameter('unit', $unit);

        $query = $queryBuilder->getQuery();
        $query->useQueryCache(false);
        $query->setHint('priceList', $this->getReference('price_list_3')->getId());
        $query->setHint(
            PriceShardOutputResultModifier::ORO_PRICING_SHARD_MANAGER,
            self::getContainer()->get('oro_pricing.shard_manager')
        );

        return $query->getOneOrNullResult();
    }
}
