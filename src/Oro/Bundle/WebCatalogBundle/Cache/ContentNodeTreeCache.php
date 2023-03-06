<?php

namespace Oro\Bundle\WebCatalogBundle\Cache;

use Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode;
use Psr\Cache\CacheItemPoolInterface;

/**
 * The cache for web catalog content node tree.
 */
class ContentNodeTreeCache
{
    private CacheItemPoolInterface $cache;

    private ResolvedContentNodeNormalizer $normalizer;

    public function __construct(
        CacheItemPoolInterface $cache,
        ResolvedContentNodeNormalizer $normalizer
    ) {
        $this->cache = $cache;
        $this->normalizer = $normalizer;
    }

    /**
     * Gets a content node tree from the cache
     *
     * @param int[] $scopeIds
     * @param int $treeDepth Restricts the maximum tree depth. -1 stands for unlimited.
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
     * Saves a content node tree to the cache
     *
     * @param int[] $scopeIds
     */
    public function save(int $nodeId, array $scopeIds, ?ResolvedContentNode $resolvedContentNode): bool
    {
        $cacheItem = $this->cache->getItem($this->getCacheKey($nodeId, $scopeIds));
        $cacheItem->set(null === $resolvedContentNode ? [] : $this->normalizer->normalize($resolvedContentNode));

        return $this->cache->save($cacheItem);
    }

    /**
     * Deletes a content node tree from the cache
     *
     * @param int[] $scopeIds
     */
    public function delete(int $nodeId, array $scopeIds): bool
    {
        return $this->cache->deleteItem($this->getCacheKey($nodeId, $scopeIds));
    }

    /**
     * @param array<array{int,int[]}> $scopeIdsByNodeId
     *
     * @return bool
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

    /**
     * @param int[] $scopeIds
     */
    private function getCacheKey(int $nodeId, array $scopeIds): string
    {
        sort($scopeIds);

        return sprintf('node_%s_scope_%s', $nodeId, implode('_', $scopeIds) ?: 0);
    }
}
