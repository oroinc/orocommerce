<?php

namespace Oro\Bundle\ShippingBundle\Converter;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\ExpressionLanguage\LineItemDecoratorFactory;

class ShippingContextToRuleValuesConverter
{
    /**
     * @var LineItemDecoratorFactory
     */
    protected $lineItemDecoratorFactory;

    /**
     * @param LineItemDecoratorFactory $lineItemDecoratorFactory
     */
    public function __construct(LineItemDecoratorFactory $lineItemDecoratorFactory)
    {
        $this->lineItemDecoratorFactory = $lineItemDecoratorFactory;
    }

    /**
     * @param ShippingContextInterface $context
     * @return array
     */
    public function convert(ShippingContextInterface $context)
    {
        $lineItems = $context->getLineItems();

        return [
            'lineItems' => array_map(function (ShippingLineItemInterface $lineItem) use ($lineItems) {
                return $this->lineItemDecoratorFactory->createOrderLineItemDecorator($lineItems, $lineItem);
            }, $lineItems),
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
}
