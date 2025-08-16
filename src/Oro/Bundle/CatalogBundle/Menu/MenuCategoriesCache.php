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
    private const string TITLES = 'titles';

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly LocalizedFallbackValueCollectionNormalizer $titlesNormalizer
    ) {
    }

    /**
     * @param callable $callback Callback must return an array of menu categories data
     *                           as per {@see MenuCategoriesProviderInterface::getCategories}.
     */
    #[\Override]
    public function get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null)
    {
        $wrappedCallback = function (CacheItemInterface $cacheItem) use ($callback, &$denormalizedMenuCategories) {
            $denormalizedMenuCategories = $callback($cacheItem);
            $normalizedMenuCategories = [];
            foreach ($denormalizedMenuCategories as $categoryData) {
                $categoryData[self::TITLES] = $this->titlesNormalizer->normalize($categoryData[self::TITLES] ?? []);
                $normalizedMenuCategories[] = $categoryData;
            }

            return $normalizedMenuCategories;
        };

        $menuCategories = $this->cache->get($key, $wrappedCallback, $beta, $metadata);

        return $denormalizedMenuCategories ?? $this->denormalize($menuCategories);
    }

    #[\Override]
    public function delete(string $key): bool
    {
        return $this->cache->delete($key);
    }

    private function denormalize(array $menuCategories): array
    {
        foreach ($menuCategories as &$categoryData) {
            $categoryData[self::TITLES] = $this->titlesNormalizer->denormalize(
                $categoryData[self::TITLES] ?? [],
                LocalizedFallbackValue::class
            );
        }

        return $menuCategories;
    }
}
