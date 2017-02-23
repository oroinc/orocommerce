<?php

namespace Oro\Bundle\PaymentTermBundle\Method\Config\Provider;

use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;

interface PaymentTermConfigProviderInterface
{
    /**
     * @return PaymentTermConfigInterface[]
     */
    public function getPaymentConfigs();

    /**
     * @param string $identifier
     * @return PaymentTermConfigInterface|null
     */
    public function getPaymentConfig($identifier);

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentConfig($identifier);
}
