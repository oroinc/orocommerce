<?php

namespace Oro\Bundle\PayPalBundle\Method\View\Factory;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;

interface PayPalCreditCardPaymentMethodViewFactoryInterface
{
    /**
     * @param PayPalCreditCardConfigInterface $config
     * @return PaymentMethodViewInterface
     */
    public function create(PayPalCreditCardConfigInterface $config);
}
