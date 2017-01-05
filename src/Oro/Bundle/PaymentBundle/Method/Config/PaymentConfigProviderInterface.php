<?php

namespace Oro\Bundle\PaymentBundle\Method\Config;

interface PaymentConfigProviderInterface
{
    /**
     * @return PaymentConfigInterface[]
     */
    public function getPaymentConfigs();

    /**
     * @param string $identifier
     * @return PaymentConfigInterface
     */
    public function getPaymentConfig($identifier);

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentConfig($identifier);
}
