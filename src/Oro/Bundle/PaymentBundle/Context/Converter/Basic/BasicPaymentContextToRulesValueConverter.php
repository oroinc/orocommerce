<?php

namespace Oro\Bundle\PaymentBundle\Context\Converter\Basic;

use Oro\Bundle\PaymentBundle\Context\Converter\PaymentContextToRulesValueConverterInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;
use Oro\Bundle\PaymentBundle\ExpressionLanguage\DecoratedProductLineItemFactory;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;

/**
 * Converts PaymentContext to an array
 */
class BasicPaymentContextToRulesValueConverter implements PaymentContextToRulesValueConverterInterface
{
    /**
     * @var DecoratedProductLineItemFactory
     */
    protected $decoratedProductLineItemFactory;

    public function __construct(DecoratedProductLineItemFactory $decoratedProductLineItemFactory)
    {
        $this->decoratedProductLineItemFactory = $decoratedProductLineItemFactory;
    }

    /**
     * @inheritDoc
     */
    public function convert(PaymentContextInterface $paymentContext)
    {
        $lineItems = $paymentContext->getLineItems()->toArray();
        $productIds = $this->getProductIds($lineItems);

        return [
            'lineItems' => array_map(
                function (PaymentLineItemInterface $lineItem) use ($productIds) {
                    return $this->decoratedProductLineItemFactory->createPaymentLineItemWithDecoratedProduct(
                        $lineItem,
                        $productIds
                    );
                },
                $lineItems
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
