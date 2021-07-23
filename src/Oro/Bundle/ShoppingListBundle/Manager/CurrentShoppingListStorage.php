<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Doctrine\Common\Cache\Cache;

/**
 * Provides a storage for the current shopping list identifier.
 */
class CurrentShoppingListStorage
{
    /** @var Cache */
    private $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function get(int $customerUserId): ?int
    {
        $shoppingListId = $this->cache->fetch($customerUserId);
        if (false === $shoppingListId) {
            return null;
        }

        return $shoppingListId;
    }

    public function set(int $customerUserId, ?int $shoppingListId): void
    {
        if (null === $shoppingListId) {
            $this->cache->delete($customerUserId);
        } else {
            $this->cache->save($customerUserId, $shoppingListId);
        }
    }
}
