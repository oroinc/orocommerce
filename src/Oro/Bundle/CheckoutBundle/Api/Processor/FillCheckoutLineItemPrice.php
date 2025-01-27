<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Calculates the price ("value" and "currency" fields) for {@see CheckoutLineItem}
 * and sets the calculated value to the {@see CheckoutLineItem}.
 */
class FillCheckoutLineItemPrice implements ProcessorInterface
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

        /** @var CheckoutLineItem $lineItem */
        $lineItem = $context->getData();
        if (!$this->isPriceCanBeCalculated($lineItem)) {
            return;
        }

        $productPrice = $this->getProductPrice($lineItem);
        $lineItem->setCurrency($productPrice?->getCurrency());
        $lineItem->setValue($productPrice?->getValue());
    }

    private function isPriceCanBeCalculated(CheckoutLineItem $lineItem): bool
    {
        return
            null !== $lineItem->getProduct()
            && null !== $lineItem->getProductUnit()
            && null !== $lineItem->getQuantity();
    }

    private function getProductPrice(CheckoutLineItem $lineItem): ?Price
    {
        $checkout = $lineItem->getCheckout();
        $productLineItemPrices = $this->productLineItemPriceProvider->getProductLineItemsPrices(
            [$lineItem],
            $this->priceScopeCriteriaFactory->createByContext($checkout),
            $checkout->getCurrency()
        );
        if (!isset($productLineItemPrices[0])) {
            return null;
        }

        return $productLineItemPrices[0]->getPrice();
    }
}
