<?php

namespace Oro\Bundle\PaymentBundle\Context\Converter\Basic;

use Oro\Bundle\PaymentBundle\Context\Converter\PaymentContextToRulesValueConverterInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\PaymentBundle\ExpressionLanguage\DecoratedProductLineItemFactory;

/**
 * Converts PaymentContext to an array
 */
class BasicPaymentContextToRulesValueConverter implements PaymentContextToRulesValueConverterInterface
{
    private DecoratedProductLineItemFactory $decoratedProductLineItemFactory;

    public function __construct(DecoratedProductLineItemFactory $decoratedProductLineItemFactory)
    {
        $this->decoratedProductLineItemFactory = $decoratedProductLineItemFactory;
    }

    #[\Override]
    public function convert(PaymentContextInterface $paymentContext): array
    {
        $paymentLineItems = $paymentContext->getLineItems()->toArray();
        $productIds = $this->getProductIds($paymentLineItems);

        return [
            'lineItems' => array_map(
                function (PaymentLineItem $lineItem) use ($productIds) {
                    return $this->decoratedProductLineItemFactory->createPaymentLineItemWithDecoratedProduct(
                        $lineItem,
                        $productIds
                    );
                },
                $paymentLineItems
            ),
            'billingAddress' => $paymentContext->getBillingAddress(),
            'shippingAddress' => $paymentContext->getShippingAddress(),
            'shippingOrigin' => $paymentContext->getShippingOrigin(),
            'shippingMethod' => $paymentContext->getShippingMethod(),
            'currency' => $paymentContext->getCurrency(),
            'subtotal' => $paymentContext->getSubtotal(),
            'customer' => $paymentContext->getCustomer(),
            'customerUser' => $paymentContext->getCustomerUser(),
            'total' => $paymentContext->getTotal(),
        ];
    }

    private function getProductIds(array $paymentLineItems): array
    {
        $productIds = [];
        foreach ($this->getProductsFromLineItems($paymentLineItems) as $product) {
            if ($product?->getId()) {
                $productIds[$product->getId()] = $product->getId();
            }
        }

        return array_values($productIds);
    }

    private function getProductsFromLineItems(array $paymentLineItems): \Generator
    {
        foreach ($paymentLineItems as $paymentLineItem) {
            yield $paymentLineItem->getProduct();

            foreach ($paymentLineItem->getKitItemLineItems() as $paymentKitItemLineItem) {
                yield $paymentKitItemLineItem->getProduct();
            }
        }
    }
}
