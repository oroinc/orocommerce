<?php

namespace Oro\Bundle\ProductBundle\Storage;

/**
 * Provides an interface for services that use as an abstraction
 * over the data access layer to Product Website Reindex Request Items
 */
interface ProductWebsiteReindexRequestDataStorageInterface
{
    public const DEFAULT_INSERT_CHUNK_SIZE = 1000;
    public const DEFAULT_PRODUCT_IDS_BATCH_SIZE = 100;

    public function insertMultipleRequests(
        int $relatedJobId,
        array $websiteIds,
        array $productIds,
        int $chunkSize = self::DEFAULT_INSERT_CHUNK_SIZE
    ): int;

    /**
     * @param int $relatedJobId
     * @param int $websiteId
     * @param int[] $productIds
     * @return int
     */
    public function deleteProcessedRequestItems(
        int $relatedJobId,
        int $websiteId,
        array $productIds
    ): int;

    /**
     * @param int $relatedJobId
     * @return int[]
     */
    public function getWebsiteIdsByRelatedJobId(int $relatedJobId): array;

    /**
     * @param int $relatedJobId
     * @param int $websiteId
     * @param int $batchSize
     * @return \Traversable
     */
    public function getProductIdIteratorByRelatedJobIdAndWebsiteId(
        int $relatedJobId,
        int $websiteId,
        int $batchSize = self::DEFAULT_PRODUCT_IDS_BATCH_SIZE
    ): \Traversable;
}
