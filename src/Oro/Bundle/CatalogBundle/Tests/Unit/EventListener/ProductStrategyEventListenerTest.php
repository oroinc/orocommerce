<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\EventListener\AbstractProductImportEventListener;
use Oro\Bundle\CatalogBundle\EventListener\ProductStrategyEventListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductStrategyEvent;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
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
        // Schedule deferred adding product to category
        /** @var Product $product */
        $sku = 'product1';
        $product = $this->getEntity(Product::class, ['id' => 1, 'sku' => $sku]);

        /** @var Category $oldCategory */
        $oldCategory = $this->getEntity(Category::class, ['id' => 1]);
        $this->findByProductSkuCalls[$sku] = 0;
        $this->categoriesByProduct[$sku] = $oldCategory;
        $oldCategory->addProduct($product);

        /** @var Category $newCategory */
        $newCategory = $this->getEntity(Category::class, ['id' => 2]);
        $title = 'category1';
        $this->findByDefaultTitleCalls[$title] = 0;
        $this->categoriesByTitle[$title] = $newCategory;
        $rawData = [AbstractProductImportEventListener::CATEGORY_KEY => $title];

        $event = new ProductStrategyEvent($product, $rawData);
        $this->listener->onProcessAfter($event);
        $this->listener->onClear();

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())
            ->method('flush');

        $preFlushEvent = new PreFlushEventArgs($em);
        $this->listener->preFlush($preFlushEvent);
        $this->assertEquals(1, $this->findByProductSkuCalls[$product->getSku()]);
    }

    public function testPreFlushWithEmptyProductToAdd()
    {
        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->never())
            ->method('flush');

        $event = new PreFlushEventArgs($em);
        $this->listener->preFlush($event);
    }

    public function testPreFlush()
    {
        // Schedule deferred adding product to category
        /** @var Product $product */
        $sku = 'product1';
        $product = $this->getEntity(Product::class, ['id' => 1, 'sku' => $sku]);

        /** @var Category $oldCategory */
        $oldCategory = $this->getEntity(Category::class, ['id' => 1]);
        $this->findByProductSkuCalls[$sku] = 0;
        $this->categoriesByProduct[$sku] = $oldCategory;
        $oldCategory->addProduct($product);

        /** @var Category $newCategory */
        $newCategory = $this->getEntity(Category::class, ['id' => 2]);
        $title = 'category1';
        $this->findByDefaultTitleCalls[$title] = 0;
        $this->categoriesByTitle[$title] = $newCategory;

        $rawData = [AbstractProductImportEventListener::CATEGORY_KEY => $title];
        $event = new ProductStrategyEvent($product, $rawData);
        $this->listener->onProcessAfter($event);

        // Product was not added by onProcessAfter event
        $this->assertEmpty($newCategory->getProducts());
        // Product was removed by onProcessAfter event
        $this->assertEmpty($oldCategory->getProducts());

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('flush');

        $em->expects($this->exactly(2))
            ->method('contains')
            ->withConsecutive($newCategory, $product)
            ->willReturnOnConsecutiveCalls(true, true);

        $event = new PreFlushEventArgs($em);
        $this->listener->preFlush($event);

        $this->assertCount(1, $newCategory->getProducts());
        $this->assertEquals($product, $newCategory->getProducts()->first());

        // Repeated call should be skipped
        $this->listener->preFlush($event);
    }

    public function testPreFlushNoNewCategory()
    {
        // Schedule deferred adding product to category
        /** @var Product $product */
        $sku = 'product1';
        $product = $this->getEntity(Product::class, ['id' => 1, 'sku' => $sku]);

        /** @var Category $oldCategory */
        $oldCategory = $this->getEntity(Category::class, ['id' => 1]);
        $this->findByProductSkuCalls[$sku] = 0;
        $this->categoriesByProduct[$sku] = $oldCategory;
        $oldCategory->addProduct($product);

        /** @var Category $newCategory */
        $newCategory = $this->getEntity(Category::class, ['id' => 2]);
        $title = 'category1';
        $this->findByDefaultTitleCalls[$title] = 0;
        $this->categoriesByTitle[$title] = $newCategory;

        $rawData = [AbstractProductImportEventListener::CATEGORY_KEY => $title];
        $event = new ProductStrategyEvent($product, $rawData);
        $this->listener->onProcessAfter($event);

        // Product was removed by onProcessAfter event
        $this->assertEmpty($oldCategory->getProducts());

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('flush');

        $event = new PreFlushEventArgs($em);
        $this->listener->preFlush($event);
    }

    public function testPreFlushSameCategory()
    {
        // Schedule deferred adding product to category
        /** @var Product $product */
        $sku = 'product1';
        $product = $this->getEntity(Product::class, ['id' => 1, 'sku' => $sku]);

        /** @var Category $oldCategory */
        $oldCategory = $this->getEntity(Category::class, ['id' => 1]);
        $this->findByProductSkuCalls[$sku] = 0;
        $this->categoriesByProduct[$sku] = $oldCategory;
        $oldCategory->addProduct($product);

        /** @var Category $newCategory */
        $newCategory = $this->getEntity(Category::class, ['id' => 1]);
        $title = 'category1';
        $this->findByDefaultTitleCalls[$title] = 0;
        $this->categoriesByTitle[$title] = $newCategory;

        $rawData = [AbstractProductImportEventListener::CATEGORY_KEY => $title];
        $event = new ProductStrategyEvent($product, $rawData);
        $this->listener->onProcessAfter($event);

        // Product was removed by onProcessAfter event
        $this->assertNotEmpty($oldCategory->getProducts());
        $this->assertEquals($product, $oldCategory->getProducts()->first());

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->never())
            ->method('flush');

        $event = new PreFlushEventArgs($em);
        $this->listener->preFlush($event);
    }

    public function testPreFlushWithNotManagedCategory()
    {
        // Schedule deferred adding product to category
        /** @var Product $product */
        $sku = 'product1';
        $product = $this->getEntity(Product::class, ['id' => 1, 'sku' => $sku]);

        /** @var Category $oldCategory */
        $oldCategory = $this->getEntity(Category::class, ['id' => 1]);
        $this->findByProductSkuCalls[$sku] = 0;
        $this->categoriesByProduct[$sku] = $oldCategory;
        $oldCategory->addProduct($product);

        /** @var Category $newCategory */
        $newCategory = $this->getEntity(Category::class, ['id' => 2]);
        $title = 'category1';
        $this->findByDefaultTitleCalls[$title] = 0;
        $this->categoriesByTitle[$title] = $newCategory;

        $rawData = [AbstractProductImportEventListener::CATEGORY_KEY => $title];
        $event = new ProductStrategyEvent($product, $rawData);
        $this->listener->onProcessAfter($event);

        // Product was not added by onProcessAfter event
        $this->assertEmpty($newCategory->getProducts());
        // Product was removed by onProcessAfter event
        $this->assertEmpty($oldCategory->getProducts());

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('flush');

        $em->expects($this->exactly(1))
            ->method('contains')
            ->with($newCategory)
            ->willReturn(false);

        $event = new PreFlushEventArgs($em);
        $this->listener->preFlush($event);

        $this->assertEmpty($newCategory->getProducts());
    }

    public function testPreFlushWithNotManagedProduct()
    {
        // Schedule deferred adding product to category
        /** @var Product $product */
        $sku = 'product1';
        $product = $this->getEntity(Product::class, ['id' => 1, 'sku' => $sku]);

        /** @var Category $oldCategory */
        $oldCategory = $this->getEntity(Category::class, ['id' => 1]);
        $this->findByProductSkuCalls[$sku] = 0;
        $this->categoriesByProduct[$sku] = $oldCategory;
        $oldCategory->addProduct($product);

        /** @var Category $newCategory */
        $newCategory = $this->getEntity(Category::class, ['id' => 2]);
        $title = 'category1';
        $this->findByDefaultTitleCalls[$title] = 0;
        $this->categoriesByTitle[$title] = $newCategory;

        $rawData = [AbstractProductImportEventListener::CATEGORY_KEY => $title];
        $event = new ProductStrategyEvent($product, $rawData);
        $this->listener->onProcessAfter($event);

        // Product was not added by onProcessAfter event
        $this->assertEmpty($newCategory->getProducts());
        // Product was removed by onProcessAfter event
        $this->assertEmpty($oldCategory->getProducts());

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects($this->once())
            ->method('flush');

        $em->expects($this->exactly(2))
            ->method('contains')
            ->withConsecutive($newCategory, $product)
            ->willReturnOnConsecutiveCalls(true, false);

        $event = new PreFlushEventArgs($em);
        $this->listener->preFlush($event);

        $this->assertEmpty($newCategory->getProducts());
    }

    public function testPreFlushCallingFlushInsideFlush()
    {
        // Schedule deferred adding product to category
        /** @var Product $product */
        $sku = 'product1';
        $product = $this->getEntity(Product::class, ['id' => 1, 'sku' => $sku]);

        /** @var Category $oldCategory */
        $oldCategory = $this->getEntity(Category::class, ['id' => 1]);
        $this->findByProductSkuCalls[$sku] = 0;
        $this->categoriesByProduct[$sku] = $oldCategory;
        $oldCategory->addProduct($product);

        /** @var Category $newCategory */
        $newCategory = $this->getEntity(Category::class, ['id' => 2]);
        $title = 'category1';
        $this->findByDefaultTitleCalls[$title] = 0;
        $this->categoriesByTitle[$title] = $newCategory;

        $rawData = [AbstractProductImportEventListener::CATEGORY_KEY => $title];
        $event = new ProductStrategyEvent($product, $rawData);
        $this->listener->onProcessAfter($event);

        $this->assertArrayHasKey($title, $this->categoriesByTitle);

        // Product was not added by onProcessAfter event
        $this->assertEmpty($newCategory->getProducts());

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $event = new PreFlushEventArgs($em);

        $em->expects($this->once())
            ->method('flush')
            ->willReturnCallback(function () use ($event) {
                $this->listener->preFlush($event);
            });

        $em->expects($this->exactly(2))
            ->method('contains')
            ->withConsecutive($newCategory, $product)
            ->willReturnOnConsecutiveCalls(true, false);

        $this->listener->preFlush($event);

        $this->assertEmpty($newCategory->getProducts());
    }
}
