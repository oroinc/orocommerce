<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\Factory;

use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Creates product line item price from a product line item and product price by delegating calls to inner factories.
 */
class ProductLineItemPriceFactory implements ProductLineItemPriceFactoryInterface
{
    /** @var iterable<ProductLineItemPriceFactoryInterface> */
    private iterable $innerFactories;

    /**
     * @param iterable<ProductLineItemPriceFactoryInterface> $innerFactories
     */
    public function __construct(iterable $innerFactories)
    {
        $this->innerFactories = $innerFactories;
    }

    #[\Override]
    public function createForProductLineItem(
        ProductLineItemInterface $lineItem,
        ProductPriceInterface $productPrice
    ): ?ProductLineItemPrice {
        foreach ($this->innerFactories as $innerFactory) {
            if ($innerFactory->isSupported($lineItem, $productPrice)) {
                return $innerFactory->createForProductLineItem($lineItem, $productPrice);
            }
        }

        return null;
    }

    #[\Override]
    public function isSupported(
        ProductLineItemInterface $lineItem,
        ProductPriceInterface $productPrice
    ): bool {
        foreach ($this->innerFactories as $innerFactory) {
            if ($innerFactory->isSupported($lineItem, $productPrice)) {
                return true;
            }
        }

        return false;
    }
}
