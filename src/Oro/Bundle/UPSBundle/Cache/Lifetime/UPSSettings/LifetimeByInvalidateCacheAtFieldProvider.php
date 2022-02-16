<?php

namespace Oro\Bundle\UPSBundle\Cache\Lifetime\UPSSettings;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\UPSBundle\Cache\Lifetime\LifetimeProviderInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;

/**
 * Logic of this class handles UPSSettings::invalidateCacheAt field in real time,
 * without any additional tools, such an cron commands, and so on.
 */
class LifetimeByInvalidateCacheAtFieldProvider implements LifetimeProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getLifetime(UPSSettings $settings, int $lifetime): int
    {
        $interval = 0;
        $invalidateCacheAt = $settings->getUpsInvalidateCacheAt();
        if ($invalidateCacheAt) {
            $interval = $invalidateCacheAt->getTimestamp() - time();
        }
        if ($interval <= 0 || $interval > $lifetime) {
            $interval = $lifetime;
        }

        return $interval;
    }

    /**
     * {@inheritDoc}
     *
     * Explanation:
     *
     * UPSSettings invalidateCacheAt field is null.
     * Value was saved in to the cache at 10:00 PM
     * and lifetime was set to max value, because invalidateCacheAt was null. See $this->getLifetime()
     * For example, lifetime is one day.
     * After that admin set invalidateCacheAt to 12:00 PM.
     *
     * As we know, cache of stored value will not be flushed, because we don't have any additional tools.
     *
     * For fixing this issue, invalidateCacheAt timestamp is added to cache key.
     */
    public function generateLifetimeAwareKey(UPSSettings $settings, string $key): string
    {
        $invalidateAt = $settings->getUpsInvalidateCacheAt();

        if ($settings->getUpsInvalidateCacheAt() !== null) {
            $invalidateAt = $settings->getUpsInvalidateCacheAt()->getTimestamp();
        }

        return UniversalCacheKeyGenerator::normalizeCacheKey(
            implode(
                '_',
                [
                    'transport_' . $settings->getId(),
                    $key,
                    $invalidateAt,
                ]
            )
        );
    }
}
