<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentStatusRepository;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Provides a list of available payment statuses for a given entity class.
 * It includes both out-of-the-box payment statuses and any custom statuses found.
 */
class AvailablePaymentStatusesProvider
{
    /**
     * @param ManagerRegistry $doctrine
     * @param array<string> $defaultPaymentStatuses
     * @param CacheItemPoolInterface $cache
     * @param AvailablePaymentStatusesCacheKeyProvider $availablePaymentStatusesCacheKeyProvider
     */
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly array $defaultPaymentStatuses,
        private readonly CacheItemPoolInterface $cache,
        private readonly AvailablePaymentStatusesCacheKeyProvider $availablePaymentStatusesCacheKeyProvider
    ) {
    }

    /**
     * Returns a list of available payment statuses for the given entity class.
     *
     * @param string|null $entityClass The fully qualified class name of the entity.
     *
     * @return array<string> An array of the payment statuses containing those available out-of-the-box and
     *  all custom statuses if any.
     */
    public function getAvailablePaymentStatuses(?string $entityClass = null): array
    {
        if ($entityClass === null || !class_exists($entityClass, true)) {
            return $this->defaultPaymentStatuses;
        }

        $cacheKey = $this->availablePaymentStatusesCacheKeyProvider->getCacheKey($entityClass);
        $cacheItem = $this->cache->getItem($cacheKey);

        if (!$cacheItem->isHit()) {
            /** @var PaymentStatusRepository $entityRepository */
            $entityRepository = $this->doctrine->getRepository(PaymentStatus::class);

            $result = array_values(
                array_unique(
                    array_merge(
                        $this->defaultPaymentStatuses,
                        $entityRepository->findAvailablePaymentStatusesForEntityClass($entityClass)
                    )
                )
            );

            $cacheItem->set($result);
            $this->cache->save($cacheItem);

            return $result;
        }

        return $cacheItem->get();
    }
}
