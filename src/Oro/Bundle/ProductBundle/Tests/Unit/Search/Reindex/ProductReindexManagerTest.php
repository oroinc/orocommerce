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

    /**
     * @dataProvider fieldsGroupDataProvider
     */
    public function testReindexProduct(array $fieldsGroup = null)
    {
        $event = $this->getReindexationEvents(self::PRODUCT_ID, self::WEBSITE_ID, $fieldsGroup);
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => self::PRODUCT_ID]);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, ReindexationRequestEvent::EVENT_NAME);
        $this->reindexManager->reindexProduct($product, self::WEBSITE_ID, true, $fieldsGroup);
    }

    /**
     * @dataProvider fieldsGroupDataProvider
     */
    public function testReindexProducts(array $fieldsGroup = null)
    {
        $event = $this->getReindexationEvents(self::PRODUCT_ID, self::WEBSITE_ID, $fieldsGroup);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, ReindexationRequestEvent::EVENT_NAME);
        $this->reindexManager->reindexProducts([self::PRODUCT_ID], self::WEBSITE_ID, true, $fieldsGroup);
    }

    /**
     * @dataProvider fieldsGroupDataProvider
     */
    public function testReindexProductsWithNoProducts(array $fieldsGroup = null)
    {
        $this->eventDispatcher->expects($this->never())->method('dispatch');
        $this->reindexManager->reindexProducts([], self::WEBSITE_ID, true, $fieldsGroup);
    }

    /**
     * @dataProvider fieldsGroupDataProvider
     */
    public function testReindexAllProducts(array $fieldsGroup = null)
    {
        $event = $this->getReindexationEvents([], self::WEBSITE_ID, $fieldsGroup);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, ReindexationRequestEvent::EVENT_NAME);
        $this->reindexManager->reindexAllProducts(self::WEBSITE_ID, true, $fieldsGroup);
    }

    /**
     * @param $productIds
     * @param $websiteId
     * @param array|null $fieldsGroup
     * @return ReindexationRequestEvent
     */
    protected function getReindexationEvents($productIds, $websiteId, array $fieldsGroup = null)
    {
        $productIds = is_array($productIds) ? $productIds : [$productIds];

        return new ReindexationRequestEvent([Product::class], [$websiteId], $productIds, true, $fieldsGroup);
    }

    public function fieldsGroupDataProvider(): \Generator
    {
        yield [null];
        yield [['main']];
    }
}
