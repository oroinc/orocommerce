<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Manager;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationTriggerEvent;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductIndexSchedulerTest extends \PHPUnit_Framework_TestCase
{
    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrineHelper;

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $eventDispatcher;

    /** @var ProductIndexScheduler|\PHPUnit_Framework_MockObject_MockObject */
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

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(Category::class)
            ->willReturn($this->categoryRepository);

        $this->eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

        $this->productIndexScheduler = new ProductIndexScheduler($this->doctrineHelper, $this->eventDispatcher);
    }

    public function testScheduleProductsReindex()
    {
        $categories[] = new Category();
        $productIds = [1, 2, 3];
        $websiteId = 777;
        $this->categoryRepository->expects($this->once())
            ->method('getProductIdsByCategories')
            ->with($categories)
            ->willReturn($productIds);
        $event = new ReindexationTriggerEvent(Product::class, $websiteId, $productIds);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(ReindexationTriggerEvent::EVENT_NAME, $event);

        $this->productIndexScheduler->scheduleProductsReindex($categories, $websiteId);
    }

    public function testScheduleProductsReindexNoProducts()
    {
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
}
