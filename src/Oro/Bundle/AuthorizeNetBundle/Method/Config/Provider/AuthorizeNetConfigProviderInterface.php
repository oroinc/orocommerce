<?php

namespace Oro\Bundle\AuthorizeNetBundle\Method\Config\Provider;

use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;

interface AuthorizeNetConfigProviderInterface
{
    /**
     * @return AuthorizeNetConfigInterface[]
     */
    public function getPaymentConfigs();

    /**
     * @param string $identifier
     * @return AuthorizeNetConfigInterface|null
     */
    public function getPaymentConfig($identifier);

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentConfig($identifier);
}
