<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\EventListener\ProductNormalizerEventListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;

class ProductNormalizerEventListenerTest extends AbstractProductImportEventListenerTest
{
    const CATEGORY_CLASS = 'Oro\Bundle\CatalogBundle\Entity\Category';

    /**
     * @var ProductNormalizerEventListener
     */
    protected $listener;

    public function setUp()
    {
        parent::setUp();
        $this->listener = new ProductNormalizerEventListener($this->registry, self::CATEGORY_CLASS);
    }

    public function tearDown()
    {
        unset($this->listener);
        parent::tearDown();
    }

    public function testOnNormalize()
    {
        $product = $this->getPreparedProduct();
        $event = new ProductNormalizerEvent($product, []);
        $this->listener->onNormalize($event);
        $this->assertEquals($product, $event->getProduct());

        $plainData = $event->getPlainData();
        $this->assertArrayHasKey(ProductNormalizerEventListener::CATEGORY_KEY, $plainData);
        $this->assertEquals(
            $this->categoriesByProduct[$product->getSku()]->getDefaultTitle(),
            $plainData[ProductNormalizerEventListener::CATEGORY_KEY]
        );

        // Should be used cache
        $this->listener->onNormalize($event);
        $this->assertEquals(1, $this->findByProductSkuCalls[$product->getSku()]);
    }

    public function testOnClear()
    {
        $product = $this->getPreparedProduct();
        $event = new ProductNormalizerEvent($product, []);
        $this->listener->onNormalize($event);
        $this->listener->onClear();
        $this->listener->onNormalize($event);
        $this->assertEquals(2, $this->findByProductSkuCalls[$product->getSku()]);
    }

    public function testOnNormalizeWithoutCategory()
    {
        $product = (new Product())
            ->setSku('test');

        $event = new ProductNormalizerEvent($product, []);
        $this->listener->onNormalize($event);
        $this->assertArrayNotHasKey(ProductNormalizerEventListener::CATEGORY_KEY, $event->getPlainData());
    }
}
