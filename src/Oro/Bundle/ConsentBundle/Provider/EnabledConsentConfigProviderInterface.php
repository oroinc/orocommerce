<?php

namespace Oro\Bundle\ConsentBundle\Provider;

use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfig;

/**
 * Represents a service to get consents enabled in a config.
 */
interface EnabledConsentConfigProviderInterface
{
    /**
     * @return ConsentConfig[]
     */
    public function getConsentConfigs(): array;
}
