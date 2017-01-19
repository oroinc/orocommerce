<?php

namespace Oro\Bundle\PaymentTermBundle\Method\Config\Provider;

use Oro\Bundle\PaymentBundle\Method\Provider\PaymentConfigProviderInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;

interface PaymentTermConfigProviderInterface extends PaymentConfigProviderInterface
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
