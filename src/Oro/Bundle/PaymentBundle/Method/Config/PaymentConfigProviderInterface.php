<?php

namespace Oro\Bundle\PaymentBundle\Method\Config;

use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;

interface PaymentConfigProviderInterface
{
    /**
     * @return PayPalCreditCardConfigInterface[]|PayPalExpressCheckoutConfigInterface[]
     */
    public function getPaymentConfigs();

    /**
     * @param string $identifier
     * @return PayPalCreditCardConfigInterface|PayPalExpressCheckoutConfigInterface|null
     */
    public function getPaymentConfig($identifier);

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentConfig($identifier);
}
