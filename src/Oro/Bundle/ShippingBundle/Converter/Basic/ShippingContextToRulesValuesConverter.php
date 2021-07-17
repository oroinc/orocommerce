<?php

namespace Oro\Bundle\ShippingBundle\Converter\Basic;

use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Converter\ShippingContextToRulesValuesConverterInterface;
use Oro\Bundle\ShippingBundle\ExpressionLanguage\DecoratedProductLineItemFactory;

/**
 * Converts data from the context object to array.
 */
class ShippingContextToRulesValuesConverter implements ShippingContextToRulesValuesConverterInterface
{
    /**
     * @var DecoratedProductLineItemFactory
     */
    private $decoratedProductLineItemFactory;

    public function __construct(DecoratedProductLineItemFactory $decoratedProductLineItemFactory)
    {
        $this->decoratedProductLineItemFactory = $decoratedProductLineItemFactory;
    }

    public function convert(ShippingContextInterface $context): array
    {
        $lineItems = $context->getLineItems()->toArray();
        $productIds = $this->getProductIds($lineItems);

        return [
            'lineItems' => array_map(
                function (ShippingLineItemInterface $lineItem) use ($productIds) {
                    return $this->decoratedProductLineItemFactory->createShippingLineItemWithDecoratedProduct(
                        $lineItem,
                        $productIds
                    );
                },
                $lineItems
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

    private function getProductIds(array $lineItems): array
    {
        $productIds = array_map(
            static function (ProductHolderInterface $productHolder) {
                $product = $productHolder->getProduct();

                return $product ? $product->getId() : null;
            },
            $lineItems
        );

        return array_unique(array_filter($productIds));
    }
}
