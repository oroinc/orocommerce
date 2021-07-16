<?php

namespace Oro\Bundle\UPSBundle\Cache\Lifetime;

use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;

interface LifetimeProviderInterface
{
    public function getLifetime(UPSSettings $settings, int $lifetime): int;

    public function generateLifetimeAwareKey(UPSSettings $settings, string $key): string;
}
