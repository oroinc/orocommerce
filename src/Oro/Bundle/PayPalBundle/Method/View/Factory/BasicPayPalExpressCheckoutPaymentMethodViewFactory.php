<?php

namespace Oro\Bundle\PayPalBundle\Method\View\Factory;

use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\View\PayPalExpressCheckoutPaymentMethodView;

class BasicPayPalExpressCheckoutPaymentMethodViewFactory implements
    PayPalExpressCheckoutPaymentMethodViewFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(PayPalExpressCheckoutConfigInterface $config)
    {
        return new PayPalExpressCheckoutPaymentMethodView($config);
    }
}
