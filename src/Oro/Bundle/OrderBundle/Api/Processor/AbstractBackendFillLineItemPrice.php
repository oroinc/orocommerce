<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Brick\Math\BigDecimal;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * The base class for processors that calculate and set the price for an order line item.
 */
abstract class AbstractBackendFillLineItemPrice implements ProcessorInterface
{
    public function __construct(
        private readonly ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory,
        private readonly ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory,
        private readonly ProductPriceProviderInterface $productPriceProvider
    ) {
    }

    protected function updateLineItemPrice(OrderLineItem $lineItem): void
    {
        if ($this->isApplicable($lineItem)) {
            $lineItem->setPrice($this->calculateLineItemPrice($lineItem));
        }
    }

    private function isApplicable(OrderLineItem $lineItem): bool
    {
        return
            null !== $lineItem->getProduct()
            && null !== $lineItem->getProductUnit()
            && null !== $lineItem->getQuantity()
            && $lineItem->getProduct()->isKit();
    }

    private function calculateLineItemPrice(OrderLineItem $lineItem): Price
    {
        $price = BigDecimal::of($this->getKitPrice($lineItem)->getValue());
        foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
            $price = $price->plus(
                BigDecimal::of($kitItemLineItem->getValue() ?? 0.0)
                    ->multipliedBy($kitItemLineItem->getQuantity() ?? 1.0)
            );
        }

        return Price::create($price->toFloat(), $lineItem->getCurrency());
    }

    private function getKitPrice(OrderLineItem $lineItem): Price
    {
        $productPriceCriteria = $this->productPriceCriteriaFactory->create(
            $lineItem->getProduct(),
            $lineItem->getProductUnit(),
            $lineItem->getQuantity(),
            $lineItem->getCurrency()
        );
        $matchedPrices = $this->productPriceProvider->getMatchedPrices(
            [$productPriceCriteria],
            $this->priceScopeCriteriaFactory->createByContext($lineItem->getOrder())
        );

        return $matchedPrices
            ? current($matchedPrices)
            : $lineItem->getPrice();
    }
}
