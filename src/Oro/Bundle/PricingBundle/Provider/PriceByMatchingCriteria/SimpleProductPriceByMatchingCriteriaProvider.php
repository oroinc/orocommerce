<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Provider\PriceByMatchingCriteria;

use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;

/**
 * Provides a product price matching the specified product price criteria.
 */
class SimpleProductPriceByMatchingCriteriaProvider implements ProductPriceByMatchingCriteriaProviderInterface
{
    public function getProductPriceMatchingCriteria(
        ProductPriceCriteria $productPriceCriteria,
        ProductPriceCollectionDTO $productPriceCollection
    ): ?ProductPriceInterface {
        $productPrices = $productPriceCollection->getMatchingByCriteria(
            $productPriceCriteria->getProduct()->getId(),
            $productPriceCriteria->getProductUnit()->getCode(),
            $productPriceCriteria->getCurrency()
        );

        $matchedProductPrice = null;
        $matchedQuantity = 0;
        foreach ($productPrices as $productPrice) {
            if ($matchedQuantity <= $productPriceCriteria->getQuantity()
                && $productPriceCriteria->getQuantity() >= $productPrice->getQuantity()) {
                $matchedQuantity = $productPrice->getQuantity();
                $matchedProductPrice = $productPrice;
            }
        }

        return $matchedProductPrice;
    }

    public function isSupported(
        ProductPriceCriteria $productPriceCriteria,
        ProductPriceCollectionDTO $productPriceCollection
    ): bool {
        return true;
    }
}
