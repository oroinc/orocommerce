<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Provider;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Bundle\SecurityBundle\Exception\InvalidTokenSerializationException;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Provides a cache key for available payment statuses based on the current security token.
 * The cache key is a combination of the entity class and a hash of the serialized security token.
 */
class AvailablePaymentStatusesCacheKeyProvider
{
    public function __construct(
        private readonly TokenAccessorInterface $tokenAccessor,
        private readonly TokenSerializerInterface $tokenSerializer
    ) {
    }

    public function getCacheKey(string $entityClass): string
    {
        $securityToken = $this->tokenAccessor->getToken();

        $serializedToken = 'none';
        if ($securityToken !== null) {
            try {
                $serializedToken = $this->tokenSerializer->serialize($securityToken);
            } catch (InvalidTokenSerializationException $exception) {
                // If serialization fails, we can still return a cache key.
            }
        }

        return sprintf('%s|%s', $this->normalizeCacheKey($entityClass), sha1($serializedToken));
    }

    private function normalizeCacheKey(string $cacheKey): string
    {
        return false !== strpbrk($cacheKey, ItemInterface::RESERVED_CHARACTERS)
            ? rawurlencode($cacheKey)
            : $cacheKey;
    }
}
