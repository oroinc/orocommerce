<?php

namespace Oro\Bundle\PayPalBundle\Method\Factory;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;

/**
 * Defines the contract for creating PayPal Credit Card payment method instances.
 */
interface PayPalCreditCardPaymentMethodFactoryInterface
{
    /**
     * @param PayPalCreditCardConfigInterface $config
     * @return PaymentMethodInterface
     */
    public function create(PayPalCreditCardConfigInterface $config);
}
