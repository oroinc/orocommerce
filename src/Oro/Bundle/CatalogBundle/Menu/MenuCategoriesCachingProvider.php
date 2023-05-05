<?php

namespace Oro\Bundle\CatalogBundle\Menu;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserInterface;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Caches categories data coming from the inner provider.
 */
class MenuCategoriesCachingProvider implements MenuCategoriesProviderInterface
{
    private MenuCategoriesProviderInterface $menuCategoriesProvider;

    private CustomerUserRelationsProvider $customerUserRelationsProvider;

    private TokenAccessorInterface $tokenAccessor;

    private CacheInterface $cache;

    private int $cacheLifeTime = 0;

    public function __construct(
        MenuCategoriesProviderInterface $menuCategoriesProvider,
        CustomerUserRelationsProvider $customerUserRelationsProvider,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->menuCategoriesProvider = $menuCategoriesProvider;
        $this->customerUserRelationsProvider = $customerUserRelationsProvider;
        $this->tokenAccessor = $tokenAccessor;
        $this->cache = new ArrayAdapter();
    }

    public function setCache(CacheInterface $cache, int $lifeTime = 0): void
    {
        $this->cache = $cache;
        $this->cacheLifeTime = $lifeTime;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $context
     *  [
     *      'tree_depth' => int, // Max depth to expand categories children. -1 stands for unlimited.
     *      'cache_lifetime' => int,
     *  ]
     */
    public function getCategories(
        Category $category,
        ?UserInterface $user = null,
        array $context = []
    ): array {
        $treeDepth = $context['tree_depth'] ?? -1;
        $cacheKey = $this->getCacheKey($category, $treeDepth, $user);

        return $this->cache->get(
            $cacheKey,
            function (CacheItemInterface $cacheItem) use ($category, $user, $context) {
                $cacheItem->expiresAfter($context['cache_lifetime'] ?? $this->cacheLifeTime);

                return $this->menuCategoriesProvider->getCategories($category, $user, $context);
            }
        );
    }

    private function getCacheKey(
        Category $category,
        int $treeDepth,
        ?UserInterface $user
    ): string {
        $customer = $customerGroup = null;
        if ($user instanceof CustomerUserInterface) {
            $customer = $this->customerUserRelationsProvider->getCustomer($user);
            $customerGroup = $this->customerUserRelationsProvider->getCustomerGroup($user);
        }

        $keyParts = [
            (int)$category->getId(),
            $treeDepth,
            (int)$user?->getId(),
            (int)$customer?->getId(),
            (int)$customerGroup?->getId(),
            (int)$this->tokenAccessor->getOrganizationId(),
        ];

        return sprintf('menu_category_%s', implode('_', $keyParts));
    }
}
