<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Frontend;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue as InventoryStatus;
use Oro\Bundle\InventoryBundle\EventListener\Frontend\WebsiteSearchProductIndexerListener;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Stub\ProductStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Component\Testing\ReflectionUtil;

class WebsiteSearchProductIndexerListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityFallbackResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityFallbackResolver;

    /** @var UpcomingProductProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $upcomingProductProvider;

    /** @var WebsiteSearchProductIndexerListener */
    private $listener;

    protected function setUp(): void
    {
        $this->entityFallbackResolver = $this->createMock(EntityFallbackResolver::class);
        $this->upcomingProductProvider = $this->createMock(UpcomingProductProvider::class);

        $this->listener = new WebsiteSearchProductIndexerListener(
            $this->entityFallbackResolver,
            $this->upcomingProductProvider
        );
    }

    /**
     * @dataProvider contextDataProvider
     */
    public function testOnWebsiteSearchIndex(array $context): void
    {
        $product1Id = 10;
        $product1 = new ProductStub();
        ReflectionUtil::setId($product1, $product1Id);
        $product1->setInventoryStatus(new InventoryStatus('in_stock', 'In Stock'));
        $product1HighlightLowInventory = true;
        $product1LowInventoryThreshold = 1.1;
        $product1IsUpcoming = true;
        $product1AvailabilityDate = new \DateTime();

        $product2Id = 20;
        $product2 = new ProductStub();
        ReflectionUtil::setId($product2, $product2Id);
        $product2HighlightLowInventory = false;
        $product2IsUpcoming = false;

        $product3Id = 30;
        $product3 = new ProductStub();
        ReflectionUtil::setId($product3, $product3Id);
        $product3->setInventoryStatus(new InventoryStatus('in_stock', 'In Stock'));
        $product3HighlightLowInventory = false;
        $product3IsUpcoming = true;
        $product3AvailabilityDate = null;

        $this->entityFallbackResolver->expects(self::exactly(4))
            ->method('getFallbackValue')
            ->willReturnMap([
                [$product1, 'highlightLowInventory', 1, $product1HighlightLowInventory],
                [$product1, 'lowInventoryThreshold', 1, $product1LowInventoryThreshold],
                [$product2, 'highlightLowInventory', 1, $product2HighlightLowInventory],
                [$product3, 'highlightLowInventory', 1, $product3HighlightLowInventory]
            ]);
        $this->upcomingProductProvider->expects(self::exactly(3))
            ->method('isUpcoming')
            ->willReturnMap([
                [$product1, $product1IsUpcoming],
                [$product2, $product2IsUpcoming],
                [$product3, $product3IsUpcoming]
            ]);
        $this->upcomingProductProvider->expects(self::exactly(2))
            ->method('getAvailabilityDate')
            ->willReturnMap([
                [$product1, $product1AvailabilityDate],
                [$product3, $product3AvailabilityDate]
            ]);

        $event = new IndexEntityEvent(Product::class, [$product1, $product2, $product3], $context);
        $this->listener->onWebsiteSearchIndex($event);

        self::assertSame(
            [
                $product1Id => [
                    'inv_status' => [['value' => 'in_stock', 'all_text' => false]],
                    'low_inventory_threshold' => [['value' => $product1LowInventoryThreshold, 'all_text' => false]],
                    'is_upcoming' => [['value' => 1, 'all_text' => false]],
                    'availability_date' => [['value' => $product1AvailabilityDate, 'all_text' => false]]
                ],
                $product2Id => [
                    'inv_status' => [['value' => '', 'all_text' => false]],
                ],
                $product3Id => [
                    'inv_status' => [['value' => 'in_stock', 'all_text' => false]],
                    'is_upcoming' => [['value' => 1, 'all_text' => false]],
                    'availability_date' => [['value' => null, 'all_text' => false]]
                ]
            ],
            $event->getEntitiesData()
        );
    }

    public function contextDataProvider(): \Generator
    {
        yield [[]];
        yield [[AbstractIndexer::CONTEXT_FIELD_GROUPS => ['inventory']]];
    }

    public function testOnWebsiteSearchIndexUnsupportedFieldGroup(): void
    {
        $event = $this->createMock(IndexEntityEvent::class);
        $event
            ->method('getContext')
            ->willReturn([AbstractIndexer::CONTEXT_FIELD_GROUPS => ['image']]);

        $this->entityFallbackResolver->expects(self::never())
            ->method(self::anything());

        $this->upcomingProductProvider->expects(self::never())
            ->method(self::anything());

        $this->listener->onWebsiteSearchIndex($event);
    }
}
