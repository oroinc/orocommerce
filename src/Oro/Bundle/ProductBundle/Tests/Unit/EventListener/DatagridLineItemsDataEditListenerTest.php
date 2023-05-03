<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\EventListener\DatagridLineItemsDataEditListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductLineItemStub;

class DatagridLineItemsDataEditListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DatagridLineItemsDataEditListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new DatagridLineItemsDataEditListener();
    }

    public function testOnLineItemDataWhenNoLineItems(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);

        $event
            ->expects($this->once())
            ->method('getLineItems')
            ->willReturn([]);

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    /**
     * @dataProvider getProductUnitsListDataProvider
     */
    public function testOnLineItemData(array $unitPrecisions, array $unitsList): void
    {
        $product = $this->createMock(Product::class);
        $product
            ->expects($this->once())
            ->method('getUnitPrecisions')
            ->willReturn(new ArrayCollection($unitPrecisions));

        $lineItemId = 11;
        $lineItem = (new ProductLineItemStub($lineItemId))
            ->setProduct($product)
            ->setUnit(new ProductUnit());

        $event = new DatagridLineItemsDataEvent(
            [$lineItemId => $lineItem],
            [],
            $this->createMock(DatagridInterface::class),
            []
        );

        $this->listener->onLineItemData($event);

        $this->assertEquals(['units' => $unitsList], $event->getDataForLineItem($lineItemId));
    }

    public function getProductUnitsListDataProvider(): array
    {
        $itemUnit = (new ProductUnit())->setCode('item');
        $eachUnit = (new ProductUnit())->setCode('each');

        return [
            'no unit precisions' => [
                'unitPrecisions' => [],
                'unitsList' => [],
            ],
            '2 unit precisions, both enabled' => [
                'unitPrecisions' => [
                    (new ProductUnitPrecision())->setUnit($itemUnit)->setPrecision(2),
                    (new ProductUnitPrecision())->setUnit($eachUnit)->setPrecision(3),
                ],
                'expectedResult' => [
                    'item' => ['precision' => 2],
                    'each' => ['precision' => 3],
                ],
            ],
            '2 unit precisions, one not enabled' => [
                'unitPrecisions' => [
                    (new ProductUnitPrecision())->setUnit($itemUnit)->setPrecision(2),
                    (new ProductUnitPrecision())->setUnit($eachUnit)->setSell(false),
                ],
                'expectedResult' => [
                    'item' => ['precision' => 2],
                ],
            ],
        ];
    }
}
