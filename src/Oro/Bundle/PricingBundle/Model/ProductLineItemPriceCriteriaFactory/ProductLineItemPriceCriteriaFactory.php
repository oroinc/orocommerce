<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Model\ProductLineItemPriceCriteriaFactory;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Creates a product price criteria for the specified product line item by delegating calls to inner factories.
 */
class ProductLineItemPriceCriteriaFactory implements ProductLineItemPriceCriteriaFactoryInterface
{
    /** @var iterable<ProductLineItemPriceCriteriaFactoryInterface> */
    private iterable $innerFactories;

    /**
     * @param iterable<ProductLineItemPriceCriteriaFactoryInterface> $innerFactories
     */
    public function __construct(iterable $innerFactories)
    {
        $this->innerFactories = $innerFactories;
    }

    #[\Override]
    public function createFromProductLineItem(
        ProductLineItemInterface $lineItem,
        ?string $currency
    ): ?ProductPriceCriteria {
        foreach ($this->innerFactories as $innerFactory) {
            if ($innerFactory->isSupported($lineItem, $currency)) {
                return $innerFactory->createFromProductLineItem($lineItem, $currency);
            }
        }

        return null;
    }

    #[\Override]
    public function isSupported(
        ProductLineItemInterface $lineItem,
        ?string $currency
    ): bool {
        foreach ($this->innerFactories as $innerFactory) {
            if ($innerFactory->isSupported($lineItem, $currency)) {
                return true;
            }
        }

        return false;
    }
}
