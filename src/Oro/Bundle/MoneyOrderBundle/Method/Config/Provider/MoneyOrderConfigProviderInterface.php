<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Config\Provider;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;

/**
 * Defines the contract for providing Money Order payment method configurations.
 *
 * This interface specifies methods for retrieving Money Order payment configurations, either
 * as a complete collection or individually by payment method identifier. Implementations are
 * responsible for managing the availability and accessibility of Money Order configurations
 * throughout the payment system.
 */
interface MoneyOrderConfigProviderInterface
{
    /**
     * @return MoneyOrderConfigInterface[]
     */
    public function getPaymentConfigs();

    /**
     * @param string $identifier
     * @return MoneyOrderConfigInterface|null
     */
    public function getPaymentConfig($identifier);

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentConfig($identifier);
}
