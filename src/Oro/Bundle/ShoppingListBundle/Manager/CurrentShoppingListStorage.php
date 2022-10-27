<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Provides a storage for the current shopping list identifier.
 */
class CurrentShoppingListStorage
{
    private CacheItemPoolInterface $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function get(int $customerUserId): ?int
    {
        $cacheItem = $this->cache->getItem((string) $customerUserId);
        return $cacheItem->isHit() ? $cacheItem->get() : null;
    }

    public function set(int $customerUserId, ?int $shoppingListId): void
    {
        if (null === $shoppingListId) {
            $this->cache->deleteItem((string) $customerUserId);
        } else {
            $cacheItem = $this->cache->getItem((string) $customerUserId);
            $this->cache->save($cacheItem->set($shoppingListId));
        }
    }
}
