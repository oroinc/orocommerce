<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Config\Provider;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;

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
