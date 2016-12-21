<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Oro\Bundle\CatalogBundle\EventListener\AbstractProductImportEventListener;
use Oro\Bundle\CatalogBundle\EventListener\ProductStrategyEventListener;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductStrategyEvent;

class ProductStrategyEventListenerTest extends AbstractProductImportEventListenerTest
{
    /**
     * @var ProductStrategyEventListener
     */
    protected $listener;

    public function setUp()
    {
        parent::setUp();
        $this->listener = new ProductStrategyEventListener($this->registry, self::CATEGORY_CLASS);
    }

    public function tearDown()
    {
        unset($this->listener);
        parent::tearDown();
    }

    public function testOnProcessAfterWithoutCategoryKey()
    {
        $product = $this->getPreparedProduct();
        $event = new ProductStrategyEvent($product, []);
        $this->listener->onProcessAfter($event);

        $this->assertEquals(0, $this->findByProductSkuCalls[$product->getSku()]);
        $this->assertCount(0, $this->findByDefaultTitleCalls);
    }

    public function testOnProcessAfter()
    {
        $product = $this->getPreparedProduct();
        $title = $this->prepareTitle('some title');

        $rawData = [AbstractProductImportEventListener::CATEGORY_KEY => $title];
        $event = new ProductStrategyEvent($product, $rawData);
        $this->listener->onProcessAfter($event);

        $this->assertArrayHasKey($title, $this->categoriesByTitle);
        $category = $this->categoriesByTitle[$title];

        $this->listener->onProcessAfter($event);
        $this->assertEquals(1, $this->findByDefaultTitleCalls[$title]);
    }

    public function testOnProcessAfterWithoutCategory()
    {
        $product = $this->getPreparedProduct();
        $category = $this->categoriesByProduct[$product->getSku()];

        $title = 'some title';

        $rawData = [AbstractProductImportEventListener::CATEGORY_KEY => $title];
        $event = new ProductStrategyEvent($product, $rawData);
        $this->listener->onProcessAfter($event);

        $this->assertEmpty($category->getProducts());
    }

    public function testOnClear()
    {
        $product = $this->getPreparedProduct();
        $rawData = [AbstractProductImportEventListener::CATEGORY_KEY => 'some title'];

        $event = new ProductStrategyEvent($product, $rawData);
        $this->listener->onProcessAfter($event);
        $this->listener->onClear();
        $this->listener->onProcessAfter($event);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $em->expects($this->never())
            ->method('flush');

        $preFlushEvent = new PreFlushEventArgs($em);
        $this->listener->preFlush($preFlushEvent);
        $this->assertEquals(2, $this->findByProductSkuCalls[$product->getSku()]);
    }

    public function testPreFlushWithEmptyProductToAdd()
    {
        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder(EntityManagerInterface::class)->getMock();

        $em->expects($this->never())
            ->method('flush');

        $event = new PreFlushEventArgs($em);
        $this->listener->preFlush($event);
    }

    public function testPreFlush()
    {
        // Schedule deferred adding product to category
        $product = $this->getPreparedProduct();
        $title = $this->prepareTitle('some title');

        $rawData = [AbstractProductImportEventListener::CATEGORY_KEY => $title];
        $event = new ProductStrategyEvent($product, $rawData);
        $this->listener->onProcessAfter($event);

        $this->assertArrayHasKey($title, $this->categoriesByTitle);
        $category = $this->categoriesByTitle[$title];

        // Product was not added by onProcessAfter event
        $this->assertEmpty($category->getProducts());

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder(EntityManagerInterface::class)->getMock();

        $em->expects($this->once())
            ->method('flush');

        $em->expects($this->exactly(2))
            ->method('contains')
            ->withConsecutive($category, $product)
            ->willReturnOnConsecutiveCalls(true, true);

        $event = new PreFlushEventArgs($em);
        $this->listener->preFlush($event);

        $this->assertCount(1, $category->getProducts());
        $this->assertEquals($product, $category->getProducts()->first());

        // Repeated call should be skipped
        $this->listener->preFlush($event);
    }

    public function testPreFlushWithNotManagedCategory()
    {
        // Schedule deferred adding product to category
        $product = $this->getPreparedProduct();
        $title = $this->prepareTitle('some title');

        $rawData = [AbstractProductImportEventListener::CATEGORY_KEY => $title];
        $event = new ProductStrategyEvent($product, $rawData);
        $this->listener->onProcessAfter($event);

        $this->assertArrayHasKey($title, $this->categoriesByTitle);
        $category = $this->categoriesByTitle[$title];

        // Product was not added by onProcessAfter event
        $this->assertEmpty($category->getProducts());

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder(EntityManagerInterface::class)->getMock();

        $em->expects($this->once())
            ->method('flush');

        $em->expects($this->exactly(1))
            ->method('contains')
            ->with($category)
            ->willReturn(false);

        $event = new PreFlushEventArgs($em);
        $this->listener->preFlush($event);

        $this->assertEmpty($category->getProducts());
    }

    public function testPreFlushWithNotManagedProduct()
    {
        // Schedule deferred adding product to category
        $product = $this->getPreparedProduct();
        $title = $this->prepareTitle('some title');

        $rawData = [AbstractProductImportEventListener::CATEGORY_KEY => $title];
        $event = new ProductStrategyEvent($product, $rawData);
        $this->listener->onProcessAfter($event);

        $this->assertArrayHasKey($title, $this->categoriesByTitle);
        $category = $this->categoriesByTitle[$title];

        // Product was not added by onProcessAfter event
        $this->assertEmpty($category->getProducts());

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder(EntityManagerInterface::class)->getMock();

        $em->expects($this->once())
            ->method('flush');

        $em->expects($this->exactly(2))
            ->method('contains')
            ->withConsecutive($category, $product)
            ->willReturnOnConsecutiveCalls(true, false);

        $event = new PreFlushEventArgs($em);
        $this->listener->preFlush($event);

        $this->assertEmpty($category->getProducts());
    }

    public function testPreFlushCallingFlushInsideFlush()
    {
        // Schedule deferred adding product to category
        $product = $this->getPreparedProduct();
        $title = $this->prepareTitle('some title');

        $rawData = [AbstractProductImportEventListener::CATEGORY_KEY => $title];
        $event = new ProductStrategyEvent($product, $rawData);
        $this->listener->onProcessAfter($event);

        $this->assertArrayHasKey($title, $this->categoriesByTitle);
        $category = $this->categoriesByTitle[$title];

        // Product was not added by onProcessAfter event
        $this->assertEmpty($category->getProducts());

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder(EntityManagerInterface::class)->getMock();

        $event = new PreFlushEventArgs($em);

        $em->expects($this->once())
            ->method('flush')
            ->willReturnCallback(function () use ($event) {
                $this->listener->preFlush($event);
            });

        $em->expects($this->exactly(2))
            ->method('contains')
            ->withConsecutive($category, $product)
            ->willReturnOnConsecutiveCalls(true, false);

        $this->listener->preFlush($event);

        $this->assertEmpty($category->getProducts());
    }
}
