<?php

namespace Oro\Bundle\ProductBundle\Storage;

use Oro\Bundle\ProductBundle\Driver\ProductWebsiteReindexRequestDriverInterface;

/**
 * Allows managing data of Product Website Reindex Request Items independently of where it keeps.
 */
class ProductWebsiteReindexRequestDataStorage implements ProductWebsiteReindexRequestDataStorageInterface
{
    private ProductWebsiteReindexRequestDriverInterface $driver;

    public function __construct(ProductWebsiteReindexRequestDriverInterface $driver)
    {
        $this->driver = $driver;
    }

    public function insertMultipleRequests(
        int $relatedJobId,
        array $websiteIds,
        array $productIds,
        int $chunkSize = self::DEFAULT_INSERT_CHUNK_SIZE
    ): int {
        return $this->driver->insertMultipleRequests(
            $relatedJobId,
            $websiteIds,
            $productIds,
            $chunkSize
        );
    }

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
    ): int {
        return $this->driver->deleteProcessedRequestItems(
            $relatedJobId,
            $websiteId,
            $productIds
        );
    }

    /**
     * @param int $relatedJobId
     * @return int[]
     */
    public function getWebsiteIdsByRelatedJobId(int $relatedJobId): array
    {
        return $this->driver->getWebsiteIdsByRelatedJobId($relatedJobId);
    }

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
    ): \Traversable {
        return $this->driver->getProductIdIteratorByRelatedJobIdAndWebsiteId(
            $relatedJobId,
            $websiteId,
            $batchSize
        );
    }
}
