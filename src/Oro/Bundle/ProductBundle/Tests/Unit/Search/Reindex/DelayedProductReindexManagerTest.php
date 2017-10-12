<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Search\Reindex;

use Oro\Bundle\ProductBundle\Search\Reindex\DelayedProductReindexManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DelayedProductReindexManagerTest extends ProductReindexManagerTest
{
    /** @var DelayedProductReindexManager  */
    protected $reindexManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->reindexManager = new DelayedProductReindexManager($this->eventDispatcher);
    }

    public function testReindexProduct()
    {
        parent::testReindexProduct();
        $this->reindexManager->flushReIndexEvents();
    }

    public function testReindexProducts()
    {
        parent::testReindexProducts();
        $this->reindexManager->flushReIndexEvents();
    }

    public function testReindexProductsWithNoProducts()
    {
        parent::testReindexProductsWithNoProducts();
        $this->reindexManager->flushReIndexEvents();
    }

    public function testReindexAllProducts()
    {
        parent::testReindexAllProducts();
        $this->reindexManager->flushReIndexEvents();
    }
}
