<?php

namespace Oro\Bundle\PayPalBundle\Method\View\Factory;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;

/**
 * Defines the contract for creating PayPal Express Checkout payment method view instances.
 */
interface PayPalExpressCheckoutPaymentMethodViewFactoryInterface
{
    /**
     * @param PayPalExpressCheckoutConfigInterface $config
     * @return PaymentMethodViewInterface
     */
    public function create(PayPalExpressCheckoutConfigInterface $config);
}
