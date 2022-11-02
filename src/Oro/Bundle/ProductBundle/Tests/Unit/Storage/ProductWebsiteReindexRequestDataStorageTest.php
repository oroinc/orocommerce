<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Storage;

use Oro\Bundle\ProductBundle\Driver\ProductWebsiteReindexRequestDriverInterface;
use Oro\Bundle\ProductBundle\Storage\ProductWebsiteReindexRequestDataStorage;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductWebsiteReindexRequestDataStorageTest extends TestCase
{
    /**
     * @var ProductWebsiteReindexRequestDriverInterface|MockObject
     */
    private $driver;

    /**
     * @var WebsiteProviderInterface|MockObject
     */
    private $websiteProvider;

    /**
     * @var ProductWebsiteReindexRequestDataStorage
     */
    private $storage;

    protected function setUp(): void
    {
        $this->driver = $this->createMock(ProductWebsiteReindexRequestDriverInterface::class);
        $this->websiteProvider = $this->createMock(WebsiteProviderInterface::class);

        $this->storage = new ProductWebsiteReindexRequestDataStorage(
            $this->driver,
            $this->websiteProvider
        );
    }

    public function testInsertMultipleRequestsWithWebsites()
    {
        $relatedJobId = 1;
        $websiteIds = [10];
        $productIds = [100];
        $chunkSize = 1000;

        $this->websiteProvider->expects($this->never())
            ->method('getWebsiteIds');

        $this->driver->expects($this->once())
            ->method('insertMultipleRequests')
            ->with(
                $relatedJobId,
                $websiteIds,
                $productIds,
                $chunkSize
            );

        $this->storage->insertMultipleRequests(
            $relatedJobId,
            $websiteIds,
            $productIds,
            $chunkSize
        );
    }

    public function testInsertMultipleRequestsWithoutWebsites()
    {
        $relatedJobId = 1;
        $websiteIds = [];
        $productIds = [100];
        $chunkSize = 1000;

        $this->websiteProvider->expects($this->once())
            ->method('getWebsiteIds')
            ->willReturn([10]);

        $this->driver->expects($this->once())
            ->method('insertMultipleRequests')
            ->with(
                $relatedJobId,
                [10],
                $productIds,
                $chunkSize
            );

        $this->storage->insertMultipleRequests(
            $relatedJobId,
            $websiteIds,
            $productIds,
            $chunkSize
        );
    }
}
