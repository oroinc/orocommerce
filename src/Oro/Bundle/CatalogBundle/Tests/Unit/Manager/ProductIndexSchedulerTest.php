<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Manager;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;

class ProductIndexSchedulerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ProductReindexManager|\PHPUnit\Framework\MockObject\MockObject */
    private $reindexManager;

    /** @var ProductIndexScheduler */
    private $productIndexScheduler;

    /** @var CategoryRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryRepository;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryRepository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->reindexManager = $this->getMockBuilder(ProductReindexManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productIndexScheduler = new ProductIndexScheduler($this->doctrineHelper, $this->reindexManager);
    }

    public function testScheduleProductsReindex()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(Category::class)
            ->willReturn($this->categoryRepository);

        $categories[] = new Category();
        $productIds = [1, 2, 3];
        $websiteId = 777;
        $this->categoryRepository->expects($this->once())
            ->method('getProductIdsByCategories')
            ->with($categories)
            ->willReturn($productIds);

        $this->reindexManager->expects($this->once())
            ->method('reindexProducts')
            ->with($productIds, $websiteId, true);

        $this->productIndexScheduler->scheduleProductsReindex($categories, $websiteId);
    }
}
