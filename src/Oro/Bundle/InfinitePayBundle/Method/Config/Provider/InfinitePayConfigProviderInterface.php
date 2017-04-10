<?php

namespace Oro\Bundle\InfinitePayBundle\Method\Config\Provider;

use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;

interface InfinitePayConfigProviderInterface
{
    /**
     * @return InfinitePayConfigInterface[]
     */
    public function getPaymentConfigs();

    /**
     * @param string $identifier
     * @return InfinitePayConfigInterface|null
     */
    public function getPaymentConfig($identifier);

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentConfig($identifier);
}
