<?php

namespace Oro\Bundle\UPSBundle\Cache\Lifetime;

use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;

interface LifetimeProviderInterface
{
    /**
     * @param UPSSettings $settings
     * @param int         $lifetime
     *
     * @return int
     */
    public function getLifetime(UPSSettings $settings, int $lifetime): int;

    /**
     * @param UPSSettings $settings
     * @param string      $key
     *
     * @return string
     */
    public function generateLifetimeAwareKey(UPSSettings $settings, string $key): string;
}
