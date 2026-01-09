<?php

namespace Oro\Bundle\PayPalBundle\Method\Factory;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;

/**
 * Defines the contract for creating PayPal Express Checkout payment method instances.
 */
interface PayPalExpressCheckoutPaymentMethodFactoryInterface
{
    /**
     * @param PayPalExpressCheckoutConfigInterface $config
     * @return PaymentMethodInterface
     */
    public function create(PayPalExpressCheckoutConfigInterface $config);
}
