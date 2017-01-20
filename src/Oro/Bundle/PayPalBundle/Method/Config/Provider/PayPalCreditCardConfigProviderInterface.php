<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Provider;

use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;

interface PayPalCreditCardConfigProviderInterface
{
    /**
     * @return PayPalCreditCardConfigInterface[]
     */
    public function getPaymentConfigs();

    /**
     * @param string $identifier
     * @return PayPalCreditCardConfigInterface|null
     */
    public function getPaymentConfig($identifier);

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentConfig($identifier);
}
