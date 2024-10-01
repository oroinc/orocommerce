<?php

namespace Oro\Bundle\PricingBundle\Debug\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Return price ids of prices chosen by the merge by priority strategy.
 *
 * @internal This service is applicable for pricing debug purpose only.
 */
class MergeByPriorityPricesSelectedPriceProvider implements SelectedPriceProviderInterface
{
    public function __construct(
        private ManagerRegistry $registry,
        private ShardManager $shardManager
    ) {
    }

    #[\Override]
    public function getSelectedPricesIds(array $priceListRelations, Product $product): array
    {
        $priceRepo = $this->registry->getRepository(ProductPrice::class);

        $breakByMergeFalse = false;
        $result = [];
        $presentKeys = [];
        foreach ($priceListRelations as $priceListRelation) {
            // For merge allowed=false when it is 1st source - get prices from this PL only and skip all other
            if (empty($result) && !$priceListRelation->isMergeAllowed()) {
                $breakByMergeFalse = true;
            }
            // For merge allowed=false when there are prices for product - skip such PL
            if (!empty($result) && !$priceListRelation->isMergeAllowed()) {
                continue;
            }

            $priceList = $priceListRelation->getPriceList();
            $prices = $priceRepo->findByPriceList($this->shardManager, $priceList, ['product' => $product]);
            foreach ($prices as $price) {
                $key = sprintf(
                    '%s_%s_%s',
                    $price->getQuantity(),
                    $price->getProductUnitCode(),
                    $price->getPrice()->getCurrency()
                );
                if (!empty($presentKeys[$key])) {
                    continue;
                }
                $presentKeys[$key] = true;

                $result[] = $price->getId();
            }

            if ($breakByMergeFalse) {
                return $result;
            }
        }

        return $result;
    }
}
