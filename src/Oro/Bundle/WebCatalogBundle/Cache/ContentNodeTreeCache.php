<?php

namespace Oro\Bundle\WebCatalogBundle\Cache;

use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Psr\Cache\CacheItemPoolInterface;

/**
 * The cache for web catalog content node tree.
 */
class ContentNodeTreeCache
{
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly ResolvedContentNodeNormalizer $normalizer
    ) {
    }

    /**
     * Gets a content node tree from the cache.
     */
    public function fetch(int $nodeId, array $scopeIds, int $treeDepth = -1): ResolvedContentNode|false|null
    {
        $cacheItem = $this->cache->getItem($this->getCacheKey($nodeId, $scopeIds));
        if (!$cacheItem->isHit()) {
            return false;
        }

        $cachedData = $cacheItem->get();
        if (empty($cachedData)) {
            return null;
        }

        return $this->normalizer->denormalize($cachedData, ['tree_depth' => $treeDepth]);
    }

    /**
     * Saves a content node tree to the cache.
     */
    public function save(int $nodeId, array $scopeIds, ?ResolvedContentNode $resolvedContentNode): bool
    {
        $cacheItem = $this->cache->getItem($this->getCacheKey($nodeId, $scopeIds));
        $cacheItem->set(null === $resolvedContentNode ? [] : $this->normalizer->normalize($resolvedContentNode));

        return $this->cache->save($cacheItem);
    }

    /**
     * Deletes a content node tree from the cache.
     */
    public function delete(int $nodeId, array $scopeIds): bool
    {
        return $this->cache->deleteItem($this->getCacheKey($nodeId, $scopeIds));
    }

    /**
     * Deletes content node trees from the cache.
     *
     * @param array $scopeIdsByNodeId [node id => [scope id, ...], ...]
     */
    public function deleteMultiple(array $scopeIdsByNodeId): bool
    {
        $cacheKeys = [];
        foreach ($scopeIdsByNodeId as [$nodeId, $scopeIds]) {
            $cacheKeys[] = $this->getCacheKey($nodeId, $scopeIds);
        }

        return $cacheKeys && $this->cache->deleteItems($cacheKeys);
    }

    public function clear(): bool
    {
        return $this->cache->clear();
    }

    private function getCacheKey(int $nodeId, array $scopeIds): string
    {
        sort($scopeIds);

        return \sprintf('node_%s_scope_%s', $nodeId, implode('_', $scopeIds) ?: 0);
    }
}
