<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Provider\PriceByMatchingCriteria;

use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;

/**
 * Provides a product price matching specified product price criteria by delegating calls to inner providers.
 */
class ProductPriceByMatchingCriteriaProvider implements ProductPriceByMatchingCriteriaProviderInterface
{
    /** @var iterable<ProductPriceByMatchingCriteriaProviderInterface> */
    private iterable $innerProviders;

    /**
     * @param iterable<ProductPriceByMatchingCriteriaProviderInterface> $innerProviders
     */
    public function __construct(iterable $innerProviders)
    {
        $this->innerProviders = $innerProviders;
    }

    #[\Override]
    public function getProductPriceMatchingCriteria(
        ProductPriceCriteria $productPriceCriteria,
        ProductPriceCollectionDTO $productPriceCollection
    ): ?ProductPriceInterface {
        foreach ($this->innerProviders as $innerProvider) {
            if (!$innerProvider->isSupported($productPriceCriteria, $productPriceCollection)) {
                continue;
            }

            return $innerProvider->getProductPriceMatchingCriteria($productPriceCriteria, $productPriceCollection);
        }

        return null;
    }

    #[\Override]
    public function isSupported(
        ProductPriceCriteria $productPriceCriteria,
        ProductPriceCollectionDTO $productPriceCollection
    ): bool {
        foreach ($this->innerProviders as $innerProvider) {
            if ($innerProvider->isSupported($productPriceCriteria, $productPriceCollection)) {
                return true;
            }
        }

        return false;
    }
}
