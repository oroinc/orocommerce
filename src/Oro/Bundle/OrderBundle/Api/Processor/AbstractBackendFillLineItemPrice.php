<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Brick\Math\BigDecimal;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Calculates and sets the price for {@see OrderLineItem}.
 *
 *  The calculated price is the result of the following:
 *    a product kit product price taken from a price list + product kit item line items' prices taken
 *    from a context (i.e. prices submitted in a request).
 *
 * Works only for a product kit line item.
 */
abstract class AbstractBackendFillLineItemPrice implements ProcessorInterface
{
    public function __construct(
        protected ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory,
        protected ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory,
        protected ProductPriceProviderInterface $productPriceProvider,
    ) {
    }

    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */
        if (!$context->getForm()->isValid()) {
            return;
        }

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->getOrderLineItem($context);
        if (!$this->isApplicable($lineItem)) {
            return;
        }

        $price = $this->calculateLineItemPrice($lineItem);
        $lineItem->setPrice($price);
    }

    protected function isApplicable(OrderLineItem $lineItem): bool
    {
        return
            null !== $lineItem->getProduct()
            && null !== $lineItem->getProductUnit()
            && null !== $lineItem->getQuantity()
            && $lineItem->getProduct()->isKit();
    }

    protected function calculateLineItemPrice(OrderLineItem $lineItem): Price
    {
        $kitPrice = current($this->getMatchedPrices($lineItem));
        if (!$kitPrice) {
            $kitPrice = $lineItem->getPrice();
        }

        $price = BigDecimal::of($kitPrice->getValue());
        foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
            $kitItemValue = $kitItemLineItem->getValue() ?? 0;
            $kitItemQuantity = $kitItemLineItem->getQuantity() ?? 1;

            $price = $price->plus(BigDecimal::of($kitItemValue)->multipliedBy($kitItemQuantity));
        }

        return Price::create($price->toFloat(), $lineItem->getCurrency());
    }

    protected function getMatchedPrices(OrderLineItem $lineItem): array
    {
        $productPriceCriteria = $this->productPriceCriteriaFactory->create(
            $lineItem->getProduct(),
            $lineItem->getProductUnit(),
            $lineItem->getQuantity(),
            $lineItem->getCurrency()
        );

        return $this->productPriceProvider->getMatchedPrices(
            [$productPriceCriteria],
            $this->priceScopeCriteriaFactory->createByContext($lineItem->getOrder())
        );
    }

    abstract protected function getOrderLineItem(CustomizeFormDataContext $context): OrderLineItem;
}
