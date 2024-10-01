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
    #[\Override]
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

    #[\Override]
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
