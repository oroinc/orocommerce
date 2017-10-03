<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\InventoryBundle\EventListener\Frontend\ProductDatagridListener;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryQuantityManager;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

use Oro\Component\Testing\Unit\EntityTrait;

class ProductDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var LowInventoryQuantityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $lowInventoryQuantityManager;

    /**
     * @var ProductDatagridListener
     */
    private $listener;

    public function setUp()
    {
        $this->lowInventoryQuantityManager = $this->getMockBuilder(LowInventoryQuantityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new ProductDatagridListener($this->lowInventoryQuantityManager);
    }

    public function testOnPreBuild()
    {
        $config = DatagridConfiguration::createNamed('grid-name', []);
        $event  = new PreBuild($config, new ParameterBag());

        $this->listener->onPreBuild($event);

        $this->assertEquals(
            [
                'name'       => 'grid-name',
                'properties' => [
                    ProductDatagridListener::COLUMN_LOW_INVENTORY => [
                        'type'          => 'field',
                        'frontend_type' => PropertyInterface::TYPE_BOOLEAN
                    ]
                ],
            ],
            $config->toArray()
        );
    }

    public function testOnResultAfterNoLowInventory()
    {
        /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);

        $record = new ResultRecord(['id' => 777]);
        $products = [
            ['productId' => 777, 'productUnit' => null]
        ];

        $this->lowInventoryQuantityManager
            ->expects($this->once())
            ->method('isLowInventoryCollection')
            ->with($products)
            ->willReturn([]);
        /** @var SearchQueryInterface $query */
        $query = $this->createMock(SearchQueryInterface::class);

        /** @var SearchResultAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = new SearchResultAfter($datagrid, $query, [$record]);

        $this->listener->onResultAfter($event);

        $this->assertNull($record->getValue('low_inventory'));
    }

    public function testOnResultAfterNoRecords()
    {
        /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);

        $this->lowInventoryQuantityManager
            ->expects($this->once())
            ->method('isLowInventoryCollection')
            ->with([])
            ->willReturn([]);
        /** @var SearchQueryInterface $query */
        $query = $this->createMock(SearchQueryInterface::class);

        /** @var SearchResultAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = new SearchResultAfter($datagrid, $query, []);

        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfter()
    {
        /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);

        $record1 = new ResultRecord(['id' => 777, 'unit' => 'lbs']);
        $record2 = new ResultRecord(['id' => 555, 'unit' => 'lbs']);
        $record3 = new ResultRecord(['id' => 444, 'unit' => 'lbs']);

        $preparedRecords = [
            ['productId' => 777, 'productUnit' => 'lbs'],
            ['productId' => 555, 'productUnit' => 'lbs'],
            ['productId' => 444, 'productUnit' => 'lbs']
        ];

        $this->lowInventoryQuantityManager
            ->expects($this->once())
            ->method('isLowInventoryCollection')
            ->with($preparedRecords)
            ->willReturn([
                777 => true,
                555 => false
            ]);
        /** @var SearchQueryInterface $query */
        $query = $this->createMock(SearchQueryInterface::class);

        /** @var SearchResultAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = new SearchResultAfter($datagrid, $query, [$record1, $record2, $record3]);

        $this->listener->onResultAfter($event);

        $this->assertEquals(true, $record1->getValue('low_inventory'));
        $this->assertEquals(false, $record2->getValue('low_inventory'));
        $this->assertNull($record3->getValue('low_inventory'));
    }
}
