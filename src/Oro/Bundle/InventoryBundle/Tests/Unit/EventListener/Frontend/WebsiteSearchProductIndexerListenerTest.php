<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Frontend;

use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue as InventoryStatus;
use Oro\Bundle\InventoryBundle\EventListener\Frontend\WebsiteSearchProductIndexerListener;
use Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Stub\ProductStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Component\Testing\ReflectionUtil;

class WebsiteSearchProductIndexerListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var WebsiteSearchProductIndexerListener */
    private $listener;

    protected function setUp(): void
    {
        $this->markTestSkipped('BB-21644');

        $this->listener = new WebsiteSearchProductIndexerListener();
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

        $product2Id = 20;
        $product2 = new ProductStub();
        ReflectionUtil::setId($product2, $product2Id);

        $product3Id = 30;
        $product3 = new ProductStub();
        ReflectionUtil::setId($product3, $product3Id);
        $product3->setInventoryStatus(new InventoryStatus('in_stock', 'In Stock'));

        $event = new IndexEntityEvent(Product::class, [$product1, $product2, $product3], $context);
        $this->listener->onWebsiteSearchIndex($event);

        self::assertSame(
            [
                $product1Id => [
                    'inventory_status' => [['value' => 'in_stock', 'all_text' => false]],
                ],
                $product2Id => [
                    'inventory_status' => [['value' => '', 'all_text' => false]],
                ],
                $product3Id => [
                    'inventory_status' => [['value' => 'in_stock', 'all_text' => false]],
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
        $event = new IndexEntityEvent(
            Product::class,
            [new ProductStub()],
            [AbstractIndexer::CONTEXT_FIELD_GROUPS => ['main']]
        );
        $this->listener->onWebsiteSearchIndex($event);
        $this->assertEmpty($event->getEntitiesData());
    }
}
