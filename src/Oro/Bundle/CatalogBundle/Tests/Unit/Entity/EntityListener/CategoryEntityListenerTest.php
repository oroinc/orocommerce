<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\EntityListener\CategoryEntityListener;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Component\Testing\Unit\EntityTrait;

class CategoryEntityListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

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
    private function setSchedulerExpectation()
    {
        $category = new Category();

        $this->productIndexScheduler->expects($this->once())
            ->method('scheduleProductsReindex')
            ->with([$category]);

        return $category;
    }

    public function testPreRemove()
    {
        $this->listener->preRemove($this->setSchedulerExpectation());
    }

    public function testPostPersist()
    {
        $this->listener->postPersist($this->setSchedulerExpectation());
    }

    public function testPreUpdate()
    {
        $category = $this->setSchedulerExpectation();
        $emMock = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $changesSet = ['some_changes' => 1];
        $event = new PreUpdateEventArgs($category, $emMock, $changesSet);
        $this->listener->preUpdate($category, $event);
    }

    public function testPreUpdateNoChangesSet()
    {
        $category = new Category();
        $emMock = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $changesSet = [];
        $event = new PreUpdateEventArgs($category, $emMock, $changesSet);
        $this->productIndexScheduler->expects($this->never())->method('scheduleProductsReindex');
        $this->listener->preUpdate($category, $event);
    }
}
