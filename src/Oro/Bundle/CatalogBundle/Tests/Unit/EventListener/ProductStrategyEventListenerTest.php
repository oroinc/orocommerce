<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

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

        $this->assertCount(1, $category->getProducts());
        $this->assertEquals($product, $category->getProducts()->first());

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
        $this->assertEquals(2, $this->findByProductSkuCalls[$product->getSku()]);
    }
}
