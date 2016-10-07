<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Entity\EntityListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\EntityListener\CategoryEntityListener;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;

class CategoryEntityListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductIndexScheduler|\PHPUnit_Framework_MockObject_MockObject */
    private $productIndexScheduler;

    /** @var CategoryEntityListener */
    private $listener;

    protected function setUp()
    {
        $this->productIndexScheduler = $this->getMockBuilder(ProductIndexScheduler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new CategoryEntityListener($this->productIndexScheduler);
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
        $this->listener->preUpdate($category);
    }
}
