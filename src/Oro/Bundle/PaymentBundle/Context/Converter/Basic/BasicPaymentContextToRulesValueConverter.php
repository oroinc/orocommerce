<?php

namespace Oro\Bundle\PaymentBundle\Context\Converter\Basic;

use Oro\Bundle\PaymentBundle\Context\Converter\PaymentContextToRulesValueConverterInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;
use Oro\Bundle\PaymentBundle\ExpressionLanguage\DecoratedProductLineItemFactory;

class BasicPaymentContextToRulesValueConverter implements PaymentContextToRulesValueConverterInterface
{
    /**
     * @var DecoratedProductLineItemFactory
     */
    protected $decoratedProductLineItemFactory;

    /**
     * @param DecoratedProductLineItemFactory $decoratedProductLineItemFactory
     */
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

        return [
            'lineItems' => array_map(function (PaymentLineItemInterface $lineItem) use ($lineItems) {
                return $this->decoratedProductLineItemFactory
                    ->createLineItemWithDecoratedProductByLineItem($lineItems, $lineItem);
            }, $lineItems),
            'billingAddress' => $paymentContext->getBillingAddress(),
            'shippingAddress' => $paymentContext->getShippingAddress(),
            'shippingOrigin' => $paymentContext->getShippingOrigin(),
            'shippingMethod' => $paymentContext->getShippingMethod(),
            'currency' => $paymentContext->getCurrency(),
            'subtotal' => $paymentContext->getSubtotal(),
            'customer' => $paymentContext->getCustomer(),
            'customerUser' => $paymentContext->getCustomerUser(),
        ];
    }
}
