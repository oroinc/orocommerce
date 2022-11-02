<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;

class ProductIndexSchedulerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductReindexManager|\PHPUnit\Framework\MockObject\MockObject */
    private $reindexManager;

    /** @var CategoryRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryRepository;

    /** @var ProductIndexScheduler */
    private $productIndexScheduler;

    protected function setUp(): void
    {
        $this->reindexManager = $this->createMock(ProductReindexManager::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($this->categoryRepository);

        $this->productIndexScheduler = new ProductIndexScheduler($doctrine, $this->reindexManager);
    }

    public function testScheduleProductsReindex()
    {
        $categories = [new Category()];
        $websiteId = 777;
        $isScheduled = true;
        $fieldGroups = ['main'];
        $productIds = [1, 2, 3];

        $this->categoryRepository->expects($this->once())
            ->method('getProductIdsByCategories')
            ->with($categories)
            ->willReturn($productIds);
        $this->reindexManager->expects($this->once())
            ->method('reindexProducts')
            ->with($productIds, $websiteId, $isScheduled, $fieldGroups);

        $this->productIndexScheduler->scheduleProductsReindex($categories, $websiteId, $isScheduled, $fieldGroups);
    }
}
