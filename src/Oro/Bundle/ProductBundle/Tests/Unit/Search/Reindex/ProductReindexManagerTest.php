<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Search\Reindex;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductReindexManagerTest extends \PHPUnit\Framework\TestCase
{
    private const PRODUCT_ID = 1;
    private const WEBSITE_ID = 777;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var ProductReindexManager */
    private $reindexManager;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->reindexManager = new ProductReindexManager($this->eventDispatcher);
    }

    private function getReindexationEvents(
        array $productIds,
        int $websiteId,
        ?array $fieldsGroup
    ): ReindexationRequestEvent {
        return new ReindexationRequestEvent([Product::class], [$websiteId], $productIds, true, $fieldsGroup);
    }

    /**
     * @dataProvider fieldsGroupDataProvider
     */
    public function testReindexProduct(?array $fieldsGroup)
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getId')
            ->willReturn(self::PRODUCT_ID);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->getReindexationEvents([self::PRODUCT_ID], self::WEBSITE_ID, $fieldsGroup),
                ReindexationRequestEvent::EVENT_NAME
            );

        $this->reindexManager->reindexProduct($product, self::WEBSITE_ID, true, $fieldsGroup);
    }

    /**
     * @dataProvider fieldsGroupDataProvider
     */
    public function testReindexProducts(?array $fieldsGroup)
    {
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->getReindexationEvents([self::PRODUCT_ID], self::WEBSITE_ID, $fieldsGroup),
                ReindexationRequestEvent::EVENT_NAME
            );

        $this->reindexManager->reindexProducts([self::PRODUCT_ID], self::WEBSITE_ID, true, $fieldsGroup);
    }

    /**
     * @dataProvider fieldsGroupDataProvider
     */
    public function testReindexProductsWithNoProducts(?array $fieldsGroup)
    {
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->reindexManager->reindexProducts([], self::WEBSITE_ID, true, $fieldsGroup);
    }

    /**
     * @dataProvider fieldsGroupDataProvider
     */
    public function testReindexAllProducts(?array $fieldsGroup)
    {
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->getReindexationEvents([], self::WEBSITE_ID, $fieldsGroup),
                ReindexationRequestEvent::EVENT_NAME
            );

        $this->reindexManager->reindexAllProducts(self::WEBSITE_ID, true, $fieldsGroup);
    }

    public function fieldsGroupDataProvider(): array
    {
        return [
            [null],
            [['main']]
        ];
    }
}
