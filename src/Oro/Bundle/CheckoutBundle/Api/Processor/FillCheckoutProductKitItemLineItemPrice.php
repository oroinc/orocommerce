<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Calculates the price ("value" and "currency" fields) for {@see CheckoutProductKitItemLineItem}
 * and sets the calculated value to the {@see CheckoutProductKitItemLineItem}.
 */
class FillCheckoutProductKitItemLineItemPrice implements ProcessorInterface
{
    public function __construct(
        private readonly ProductLineItemPriceProviderInterface $productLineItemPriceProvider,
        private readonly ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        if (!$context->getForm()->isValid()) {
            return;
        }

        /** @var CheckoutProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $context->getData();
        if (!$this->isPriceCanBeCalculated($kitItemLineItem)) {
            return;
        }

        $productPrice = $this->getProductPrice($kitItemLineItem);
        $kitItemLineItem->setCurrency($productPrice?->getCurrency());
        $kitItemLineItem->setValue($productPrice?->getValue());
    }

    private function isPriceCanBeCalculated(CheckoutProductKitItemLineItem $kitItemLineItem): bool
    {
        $lineItem = $kitItemLineItem->getLineItem();

        return
            null !== $lineItem
            && null !== $kitItemLineItem->getProduct()
            && null !== $kitItemLineItem->getProductUnit()
            && null !== $kitItemLineItem->getQuantity()
            && null !== $lineItem->getProduct()
            && null !== $lineItem->getProductUnit()
            && null !== $lineItem->getQuantity();
    }

    private function getProductPrice(CheckoutProductKitItemLineItem $kitItemLineItem): ?Price
    {
        $lineItem = $kitItemLineItem->getLineItem();
        $checkout = $lineItem->getCheckout();
        $productLineItemPrices = $this->productLineItemPriceProvider->getProductLineItemsPrices(
            [$lineItem],
            $this->priceScopeCriteriaFactory->createByContext($checkout),
            $checkout->getCurrency()
        );
        if (!isset($productLineItemPrices[0])) {
            return null;
        }

        if (!$productLineItemPrices[0] instanceof ProductKitLineItemPrice) {
            return null;
        }

        return $productLineItemPrices[0]->getKitItemLineItemPrice($kitItemLineItem)?->getPrice();
    }
}
