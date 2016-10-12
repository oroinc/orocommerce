<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Manager;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductIndexSchedulerTest extends \PHPUnit_Framework_TestCase
{
    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrineHelper;

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $eventDispatcher;

    /** @var ProductIndexScheduler */
    private $productIndexScheduler;

    /** @var CategoryRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $categoryRepository;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryRepository = $this->getMockBuilder(CategoryRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        $this->productIndexScheduler = new ProductIndexScheduler($this->doctrineHelper, $this->eventDispatcher);
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
        $event = new ReindexationRequestEvent([Product::class], [$websiteId], $productIds);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(ReindexationRequestEvent::EVENT_NAME, $event);

        $this->productIndexScheduler->scheduleProductsReindex($categories, $websiteId);
    }

    public function testScheduleProductsReindexNoProducts()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(Category::class)
            ->willReturn($this->categoryRepository);

        $categories[] = new Category();
        $productIds = [];
        $websiteId = 777;
        $this->categoryRepository->expects($this->once())
            ->method('getProductIdsByCategories')
            ->with($categories)
            ->willReturn($productIds);

        $this->eventDispatcher->expects($this->never())->method('dispatch');
        $this->productIndexScheduler->scheduleProductsReindex($categories, $websiteId);
    }
    
    public function testTriggerReindexationRequestEvent()
    {
        $productIds = [1, 2, 3];
        $websiteId = 777;
        $event = new ReindexationRequestEvent([Product::class], [$websiteId], $productIds);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(ReindexationRequestEvent::EVENT_NAME, $event);
        $this->productIndexScheduler->triggerReindexationRequestEvent($productIds, $websiteId);
    }
}
