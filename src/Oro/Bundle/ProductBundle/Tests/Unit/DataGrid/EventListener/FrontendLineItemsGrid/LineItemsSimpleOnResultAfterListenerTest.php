<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\EventListener\FrontendLineItemsGrid;

use Doctrine\ORM\AbstractQuery;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\FrontendLineItemsGrid\LineItemsSimpleOnResultAfterListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

class LineItemsSimpleOnResultAfterListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LineItemsSimpleOnResultAfterListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new LineItemsSimpleOnResultAfterListener();
    }

    public function testOnResultAfterWhenNoRecords(): void
    {
        $event = new OrmResultAfter($this->getDatagrid(), [], $this->createMock(AbstractQuery::class));
        $this->listener->onResultAfter($event);

        $this->assertCount(0, $event->getRecords());
    }

    public function testOnResultAfterWhenNoLineItems(): void
    {
        $resultRecord = $this->createMock(ResultRecordInterface::class);
        $event = new OrmResultAfter(
            $this->getDatagrid(),
            [$resultRecord],
            $this->createMock(AbstractQuery::class)
        );

        $resultRecord
            ->expects($this->never())
            ->method('setValue');

        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfterWhenLineItemsMoreThanOne(): void
    {
        $resultRecord = $this->createMock(ResultRecordInterface::class);
        $resultRecord
            ->expects($this->once())
            ->method('getValue')
            ->with('lineItemsByIds')
            ->willReturn(
                [
                    10 => $this->createMock(ProductLineItemInterface::class),
                    20 => $this->createMock(ProductLineItemInterface::class),
                ]
            );
        $event = new OrmResultAfter(
            $this->getDatagrid(),
            [$resultRecord],
            $this->createMock(AbstractQuery::class)
        );

        $resultRecord
            ->expects($this->never())
            ->method('setValue');

        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfterWhenNotLineItem(): void
    {
        $resultRecord = $this->createMock(ResultRecordInterface::class);
        $resultRecord
            ->expects($this->once())
            ->method('getValue')
            ->with('lineItemsByIds')
            ->willReturn([10 => new \stdClass()]);

        $event = new OrmResultAfter(
            $this->getDatagrid(),
            [$resultRecord],
            $this->createMock(AbstractQuery::class)
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Element lineItemsByIds was expected to contain %s objects',
                ProductLineItemInterface::class
            )
        );

        $resultRecord
            ->expects($this->never())
            ->method('setValue');

        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfterWhenNoLineItemsData(): void
    {
        $resultRecord = $this->createMock(ResultRecordInterface::class);
        $resultRecord
            ->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnMap(
                [
                    ['lineItemsByIds', [10 => $this->createMock(ProductLineItemInterface::class)]],
                    ['lineItemsDataByIds', []],
                ]
            );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Element lineItemsDataByIds was expected to contain one item');

        $event = new OrmResultAfter(
            $this->getDatagrid(),
            [$resultRecord],
            $this->createMock(AbstractQuery::class)
        );

        $resultRecord
            ->expects($this->never())
            ->method('setValue');

        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfterWithoutProduct(): void
    {
        $lineItem = $this->createMock(ProductLineItemInterface::class);
        $lineItem->expects($this->once())
            ->method('getProduct')
            ->willReturn(null);

        $resultRecord = $this->createMock(ResultRecordInterface::class);
        $resultRecord->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnMap(
                [
                    ['lineItemsByIds', [10 => $lineItem]],
                    ['lineItemsDataByIds', [10 => ['sample_key' => 'sample_value']]],
                ]
            );

        $event = new OrmResultAfter(
            $this->getDatagrid(),
            [$resultRecord],
            $this->createMock(AbstractQuery::class)
        );

        $resultRecord->expects($this->exactly(2))
            ->method('setValue')
            ->withConsecutive(
                ['isConfigurable', false],
                ['sample_key', 'sample_value']
            );

        $this->listener->onResultAfter($event);
    }

    /**
     * @dataProvider onResultDataProvider
     */
    public function testOnResultAfter(bool $isConfigurable): void
    {
        $resultRecord = $this->createMock(ResultRecordInterface::class);
        $lineItem = $this->createMock(ProductLineItemInterface::class);

        $product = $this->createMock(Product::class);
        $product
            ->expects($this->once())
            ->method('isConfigurable')
            ->willReturn($isConfigurable);

        $lineItem
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $resultRecord
            ->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnMap(
                [
                    ['lineItemsByIds', [10 => $lineItem]],
                    ['lineItemsDataByIds', [10 => ['sample_key' => 'sample_value']]],
                ]
            );

        $event = new OrmResultAfter(
            $this->getDatagrid(),
            [$resultRecord],
            $this->createMock(AbstractQuery::class)
        );

        $resultRecord
            ->expects($this->exactly(2))
            ->method('setValue')
            ->withConsecutive(
                ['isConfigurable', $isConfigurable],
                ['sample_key', 'sample_value']
            );

        $this->listener->onResultAfter($event);
    }

    public function onResultDataProvider(): array
    {
        return [
            'is configurable' => [true],
            'is not configurable' => [false],
        ];
    }

    private function getDatagrid(array $parameters = []): Datagrid
    {
        return new Datagrid(
            'test-grid',
            DatagridConfiguration::create([]),
            new ParameterBag($parameters)
        );
    }
}
