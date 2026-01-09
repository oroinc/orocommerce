<?php

namespace Oro\Bundle\UPSBundle\Cache\Lifetime;

use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;

/**
 * Defines the contract for cache lifetime providers in UPS shipping price caching.
 *
 * Implementations of this interface calculate cache lifetimes and generate cache keys
 * that are aware of UPS transport settings, particularly the invalidation timestamp.
 * This allows for dynamic cache expiration based on transport configuration.
 */
interface LifetimeProviderInterface
{
    public function getLifetime(UPSSettings $settings, int $lifetime): int;

    public function generateLifetimeAwareKey(UPSSettings $settings, string $key): string;
}
