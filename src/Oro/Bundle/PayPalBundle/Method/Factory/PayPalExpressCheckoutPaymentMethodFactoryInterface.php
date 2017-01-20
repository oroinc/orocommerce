<?php

namespace Oro\Bundle\PayPalBundle\Method\Factory;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;

interface PayPalExpressCheckoutPaymentMethodFactoryInterface
{
    /**
     * @param PayPalExpressCheckoutConfigInterface $config
     * @return PaymentMethodInterface
     */
    public function create(PayPalExpressCheckoutConfigInterface $config);
}
