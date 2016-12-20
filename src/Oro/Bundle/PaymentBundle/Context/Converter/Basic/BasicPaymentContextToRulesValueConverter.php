<?php

namespace Oro\Bundle\PaymentBundle\Context\Converter\Basic;

use Oro\Bundle\PaymentBundle\Context\Converter\PaymentContextToRulesValueConverterInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

class BasicPaymentContextToRulesValueConverter implements PaymentContextToRulesValueConverterInterface
{
    /**
     * @inheritDoc
     */
    public function convert(PaymentContextInterface $paymentContext)
    {
        return [
            'lineItems' => $paymentContext->getLineItems(),
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
