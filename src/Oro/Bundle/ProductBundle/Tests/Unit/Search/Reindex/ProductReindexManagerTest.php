<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Search\Reindex;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductReindexManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const PRODUCT_ID = 1;
    const WEBSITE_ID = 777;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $eventDispatcher;

    /** @var ProductReindexManager */
    protected $reindexManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->reindexManager = new ProductReindexManager($this->eventDispatcher);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset($this->reindexManager);
        unset($this->eventDispatcher);
    }

    public function testReindexProduct()
    {
        $event = $this->getReindexationEvents(self::PRODUCT_ID, self::WEBSITE_ID);
        /** @var $product Product|\PHPUnit\Framework\MockObject\MockObject */
        $product = $this->getEntity(Product::class, [ 'id' => self::PRODUCT_ID ]);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, ReindexationRequestEvent::EVENT_NAME);
        $this->reindexManager->reindexProduct($product, self::WEBSITE_ID);
    }

    public function testReindexProducts()
    {
        $event = $this->getReindexationEvents(self::PRODUCT_ID, self::WEBSITE_ID);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, ReindexationRequestEvent::EVENT_NAME);
        $this->reindexManager->reindexProducts([self::PRODUCT_ID], self::WEBSITE_ID);
    }

    public function testReindexProductsWithNoProducts()
    {
        $this->eventDispatcher->expects($this->never())->method('dispatch');
        $this->reindexManager->reindexProducts([], self::WEBSITE_ID);
    }

    public function testReindexAllProducts()
    {
        $event = $this->getReindexationEvents([], self::WEBSITE_ID);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, ReindexationRequestEvent::EVENT_NAME);
        $this->reindexManager->reindexAllProducts(self::WEBSITE_ID);
    }

    /**
     * @param $productIds
     * @param $websiteId
     *
     * @return ReindexationRequestEvent
     */
    protected function getReindexationEvents($productIds, $websiteId)
    {
        $productIds = is_array($productIds) ? $productIds : [$productIds];
        return new ReindexationRequestEvent([Product::class], [$websiteId], $productIds, true);
    }
}
