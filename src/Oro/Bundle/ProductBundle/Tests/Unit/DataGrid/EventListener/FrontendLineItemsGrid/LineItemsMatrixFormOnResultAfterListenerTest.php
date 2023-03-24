<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\EventListener\FrontendLineItemsGrid;

use Doctrine\ORM\AbstractQuery;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\FrontendLineItemsGrid\LineItemsDataOnResultAfterListener;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\FrontendLineItemsGrid\LineItemsMatrixFormOnResultAfterListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Provider\ProductMatrixAvailabilityProvider;

class LineItemsMatrixFormOnResultAfterListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductMatrixAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productMatrixAvailabilityProvider;

    /** @var LineItemsMatrixFormOnResultAfterListener */
    private $listener;

    protected function setUp(): void
    {
        $this->productMatrixAvailabilityProvider = $this->createMock(ProductMatrixAvailabilityProvider::class);

        $this->listener = new LineItemsMatrixFormOnResultAfterListener($this->productMatrixAvailabilityProvider);
    }

    public function testOnResultAfterWhenNoRecords(): void
    {
        $event = new OrmResultAfter($this->getDatagrid(), [], $this->createMock(AbstractQuery::class));
        $this->listener->onResultAfter($event);

        $this->assertCount(0, $event->getRecords());
    }

    public function testOnResultAfterWhenNoConfigurable(): void
    {
        $resultRecord = $this->createMock(ResultRecordInterface::class);
        $resultRecord
            ->expects($this->exactly(2))
            ->method('getValue')
            ->with('isConfigurable')
            ->willReturn(false);

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

    /**
     * @dataProvider onResultAfterWhenNoLineItemsDataProvider
     */
    public function testOnResultAfterWhenNoLineItems(array $recordData): void
    {
        $resultRecord = new ResultRecord($recordData);

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

        $this->listener->onResultAfter($event);

        $this->assertNull($resultRecord->getValue('isMatrixFormAvailable'));
    }

    public function onResultAfterWhenNoLineItemsDataProvider(): array
    {
        return [
            [
                'recordData' => ['isConfigurable' => true],
            ],
            [
                'recordData' => ['isConfigurable' => true, LineItemsDataOnResultAfterListener::LINE_ITEMS => []],
            ],
            [
                'recordData' => [
                    'isConfigurable' => true,
                    LineItemsDataOnResultAfterListener::LINE_ITEMS => [new \stdClass()]
                ],
            ],
        ];
    }

    /**
     * @dataProvider onResultAfterDataProvider
     */
    public function testOnResultAfter(
        ProductLineItemInterface $lineItem,
        Product $product,
        array $isMatrixFormAvailable,
        bool $expectedResult
    ): void {
        $resultRecord = new ResultRecord(
            [
                'isConfigurable' => true,
                LineItemsDataOnResultAfterListener::LINE_ITEMS => [$lineItem],
            ]
        );

        $this->productMatrixAvailabilityProvider
            ->expects($this->once())
            ->method('isMatrixFormAvailableForProducts')
            ->with([$product->getId() => $product])
            ->willReturn($isMatrixFormAvailable);

        $event = new OrmResultAfter(
            $this->getDatagrid(),
            [$resultRecord],
            $this->createMock(AbstractQuery::class)
        );

        $this->listener->onResultAfter($event);

        $this->assertEquals($expectedResult, $resultRecord->getValue('isMatrixFormAvailable'));
    }

    public function onResultAfterDataProvider(): array
    {
        $parentProduct = $this->createMock(Product::class);
        $parentProduct
            ->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $lineItem = $this->createMock(ProductLineItemInterface::class);
        $lineItem
            ->expects($this->any())
            ->method('getParentProduct')
            ->willReturn($parentProduct);

        $lineItemWithoutParent = $this->createMock(ProductLineItemInterface::class);
        $lineItemWithoutParent
            ->expects($this->any())
            ->method('getProduct')
            ->willReturn($parentProduct);

        return [
            'matrix form not available' => [
                'lineItem' => $lineItem,
                'product' => $parentProduct,
                'isMatrixFormAvailable' => [],
                'expectedResult' => false,
            ],
            'matrix form available' => [
                'lineItem' => $lineItem,
                'product' => $parentProduct,
                'isMatrixFormAvailable' => [$parentProduct->getId() => $parentProduct],
                'expectedResult' => true,
            ],
            'no parent product' => [
                'lineItem' => $lineItemWithoutParent,
                'product' => $parentProduct,
                'isMatrixFormAvailable' => [$parentProduct->getId() => $parentProduct],
                'expectedResult' => true,
            ],
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
