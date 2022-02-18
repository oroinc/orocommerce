<?php

namespace Oro\Bundle\ProductBundle\Driver;

/**
 * Provides an interface for services that work with Product Website Reindex Request Items on the data access layer.
 */
interface ProductWebsiteReindexRequestDriverInterface
{
    public function insertMultipleRequests(
        int $relatedJobId,
        array $websiteIds,
        array $productIds,
        int $chunkSize
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
        int $batchSize
    ): \Traversable;
}
