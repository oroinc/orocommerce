<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\ProductKit\PriceByMatchingCriteria;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\ProductKit\ProductPrice\ProductKitItemPriceDTO;
use Oro\Bundle\PricingBundle\ProductKit\ProductPrice\ProductKitPriceDTO;
use Oro\Bundle\PricingBundle\ProductKit\ProductPrice\ProductKitPriceInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\ProductKitPriceCriteria;
use Oro\Bundle\PricingBundle\Provider\PriceByMatchingCriteria\ProductPriceByMatchingCriteriaProviderInterface;

/**
 * Provides a product kit price matching the specified product price criteria.
 */
class ProductKitPriceByMatchingCriteriaProvider implements ProductPriceByMatchingCriteriaProviderInterface
{
    private ProductPriceByMatchingCriteriaProviderInterface $simpleProductPriceByMatchingCriteriaProvider;

    public function __construct(
        ProductPriceByMatchingCriteriaProviderInterface $simpleProductPriceByMatchingCriteriaProvider
    ) {
        $this->simpleProductPriceByMatchingCriteriaProvider = $simpleProductPriceByMatchingCriteriaProvider;
    }

    public function getProductPriceMatchingCriteria(
        ProductPriceCriteria|ProductKitPriceCriteria $productPriceCriteria,
        ProductPriceCollectionDTO $productPriceCollection
    ): ?ProductKitPriceInterface {
        if (!$this->isSupported($productPriceCriteria, $productPriceCollection)) {
            return null;
        }

        $productPrice = $this->simpleProductPriceByMatchingCriteriaProvider
            ->getProductPriceMatchingCriteria($productPriceCriteria, $productPriceCollection);
        $productKitPrice = new ProductKitPriceDTO(
            $productPriceCriteria->getProduct(),
            $productPrice?->getPrice() ?? Price::create(0.0, $productPriceCriteria->getCurrency()),
            $productPrice?->getQuantity() ?? 0.0,
            $productPriceCriteria->getProductUnit()
        );

        foreach ($productPriceCriteria->getKitItemsProductsPriceCriteria() as $kitItemProductPriceCriterion) {
            $eachProductPrice = $this->simpleProductPriceByMatchingCriteriaProvider
                ->getProductPriceMatchingCriteria($kitItemProductPriceCriterion, $productPriceCollection);
            if ($eachProductPrice === null) {
                continue;
            }

            $productKitPrice->addKitItemPrice(
                new ProductKitItemPriceDTO(
                    $kitItemProductPriceCriterion->getKitItem(),
                    $eachProductPrice->getProduct(),
                    $eachProductPrice->getPrice(),
                    $eachProductPrice->getQuantity(),
                    $eachProductPrice->getUnit()
                )
            );
        }

        return $productKitPrice;
    }

    public function isSupported(
        ProductPriceCriteria|ProductKitPriceCriteria $productPriceCriteria,
        ProductPriceCollectionDTO $productPriceCollection
    ): bool {
        return $productPriceCriteria instanceof ProductKitPriceCriteria;
    }
}
