<?php

namespace Oro\Bundle\ShippingBundle\Converter\Basic;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Converter\ShippingContextToRulesValuesConverterInterface;
use Oro\Bundle\ShippingBundle\ExpressionLanguage\DecoratedProductLineItemFactory;

/**
 * Converts data from the context object to array.
 */
class ShippingContextToRulesValuesConverter implements ShippingContextToRulesValuesConverterInterface
{
    private DecoratedProductLineItemFactory $decoratedProductLineItemFactory;

    public function __construct(DecoratedProductLineItemFactory $decoratedProductLineItemFactory)
    {
        $this->decoratedProductLineItemFactory = $decoratedProductLineItemFactory;
    }

    #[\Override]
    public function convert(ShippingContextInterface $context): array
    {
        $shippingLineItems = $context->getLineItems()->toArray();
        $productIds = $this->getProductIds($shippingLineItems);

        return [
            'lineItems' => array_map(
                function (ShippingLineItem $lineItem) use ($productIds) {
                    return $this->decoratedProductLineItemFactory->createShippingLineItemWithDecoratedProduct(
                        $lineItem,
                        $productIds
                    );
                },
                $shippingLineItems
            ),
            'billingAddress' => $context->getBillingAddress(),
            'shippingAddress' => $context->getShippingAddress(),
            'shippingOrigin' => $context->getShippingOrigin(),
            'paymentMethod' => $context->getPaymentMethod(),
            'currency' => $context->getCurrency(),
            'subtotal' => $context->getSubtotal(),
            'customer' => $context->getCustomer(),
            'customerUser' => $context->getCustomerUser(),
        ];
    }

    private function getProductIds(array $shippingLineItems): array
    {
        $productIds = [];
        foreach ($this->getProductsFromLineItems($shippingLineItems) as $product) {
            if ($product?->getId()) {
                $productIds[$product->getId()] = $product->getId();
            }
        }

        return array_values($productIds);
    }

    private function getProductsFromLineItems(array $shippingLineItems): \Generator
    {
        foreach ($shippingLineItems as $shippingLineItem) {
            yield $shippingLineItem->getProduct();

            foreach ($shippingLineItem->getKitItemLineItems() as $shippingKitItemLineItem) {
                yield $shippingKitItemLineItem->getProduct();
            }
        }
    }
}
