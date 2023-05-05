<?php

namespace Oro\Bundle\CatalogBundle\Menu;

use Oro\Bundle\LocaleBundle\Cache\Normalizer\LocalizedFallbackValueCollectionNormalizer;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Psr\Cache\CacheItemInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Categories data collection cache. Normalizes/denormalizes category data collection before/after caching.
 */
class MenuCategoriesCache implements CacheInterface
{
    private CacheInterface $cache;

    private LocalizedFallbackValueCollectionNormalizer $titlesNormalizer;

    public function __construct(
        CacheInterface $cache,
        LocalizedFallbackValueCollectionNormalizer $localizedFallbackValueCollectionNormalizer
    ) {
        $this->cache = $cache;
        $this->titlesNormalizer = $localizedFallbackValueCollectionNormalizer;
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $callback Callback must return an array of menu categories data
     *                           as per {@see MenuCategoriesProviderInterface::getCategories}.
     */
    public function get(string $key, callable $callback, float $beta = null, array &$metadata = null)
    {
        $wrappedCallback = function (CacheItemInterface $cacheItem) use ($callback, &$denormalizedMenuCategories) {
            $denormalizedMenuCategories = $callback($cacheItem);
            $normalizedMenuCategories = [];
            foreach ($denormalizedMenuCategories as $categoryData) {
                $categoryData['titles'] = $this->titlesNormalizer->normalize($categoryData['titles'] ?? []);
                $normalizedMenuCategories[] = $categoryData;
            }

            return $normalizedMenuCategories;
        };

        $menuCategories = $this->cache->get($key, $wrappedCallback, $beta, $metadata);

        return $denormalizedMenuCategories ?? $this->denormalize($menuCategories);
    }

    private function denormalize(array $menuCategories): array
    {
        foreach ($menuCategories as &$categoryData) {
            $categoryData['titles'] = $this->titlesNormalizer
                ->denormalize($categoryData['titles'] ?? [], LocalizedFallbackValue::class);
        }

        return $menuCategories;
    }

    public function delete(string $key): bool
    {
        return $this->cache->delete($key);
    }
}
