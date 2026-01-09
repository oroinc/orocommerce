<?php

namespace Oro\Bundle\PayPalBundle\Method\View\Factory;

use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\View\PayPalExpressCheckoutPaymentMethodView;

/**
 * Creates PayPal Express Checkout payment method view instances.
 *
 * Constructs view objects for rendering Express Checkout payment forms.
 */
class BasicPayPalExpressCheckoutPaymentMethodViewFactory implements
    PayPalExpressCheckoutPaymentMethodViewFactoryInterface
{
    #[\Override]
    public function create(PayPalExpressCheckoutConfigInterface $config)
    {
        return new PayPalExpressCheckoutPaymentMethodView($config);
    }
}
