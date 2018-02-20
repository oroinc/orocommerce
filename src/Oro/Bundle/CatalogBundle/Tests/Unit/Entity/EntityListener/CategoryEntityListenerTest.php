<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\EntityListener\CategoryEntityListener;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Component\Cache\Layout\DataProviderCacheCleaner;
use Oro\Component\Testing\Unit\EntityTrait;

class CategoryEntityListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ProductIndexScheduler|\PHPUnit_Framework_MockObject_MockObject */
    private $productIndexScheduler;

    /** @var DataProviderCacheCleaner|\PHPUnit_Framework_MockObject_MockObject */
    private $cacheCleaner;

    /** @var CategoryEntityListener */
    private $listener;

    protected function setUp()
    {
        $this->productIndexScheduler = $this->createMock(ProductIndexScheduler::class);
        $this->cacheCleaner = $this->createMock(DataProviderCacheCleaner::class);

        $this->listener = new CategoryEntityListener(
            $this->productIndexScheduler,
            $this->cacheCleaner
        );
    }

    /**
     * @return Category
     */
    private function getCategoryAndSetSchedulerExpectation()
    {
        $category = new Category();

        $this->productIndexScheduler->expects($this->once())
            ->method('scheduleProductsReindex')
            ->with([$category]);

        $this->cacheCleaner->expects($this->once())
            ->method('clearCache');

        return $category;
    }

    public function testPreRemove()
    {
        $category = $this->getCategoryAndSetSchedulerExpectation();
        $this->listener->preRemove($category);
    }

    public function testPostPersist()
    {
        $category = $this->getCategoryAndSetSchedulerExpectation();
        $this->listener->postPersist($category);
    }

    public function testPreUpdate()
    {
        $category = $this->getCategoryAndSetSchedulerExpectation();
        $emMock = $this
            ->getMockBuilder(EntityManagerInterface::class)
            ->getMock();

        $changesSet = ['some_changes' => 1];
        $event = new PreUpdateEventArgs($category, $emMock, $changesSet);
        $this->listener->preUpdate($category, $event);
    }

    public function testPreUpdateNoChangesSet()
    {
        $category = new Category();
        $emMock = $this
            ->getMockBuilder(EntityManagerInterface::class)
            ->getMock();

        $changesSet = [];
        $event = new PreUpdateEventArgs($category, $emMock, $changesSet);
        $this->productIndexScheduler->expects($this->never())->method('scheduleProductsReindex');
        $this->listener->preUpdate($category, $event);
    }
}
