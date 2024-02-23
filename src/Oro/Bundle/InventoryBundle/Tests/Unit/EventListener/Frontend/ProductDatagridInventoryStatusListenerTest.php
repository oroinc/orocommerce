<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Frontend;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\InventoryBundle\EventListener\Frontend\ProductDatagridInventoryStatusListener;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use PHPUnit\Framework\TestCase;

class ProductDatagridInventoryStatusListenerTest extends TestCase
{
    private ProductDatagridInventoryStatusListener $listener;

    protected function setUp(): void
    {
        $enumValueProvider = $this->createMock(EnumValueProvider::class);
        $enumValueProvider
            ->expects(self::any())
            ->method('getEnumChoicesByCode')
            ->with('prod_inventory_status')
            ->willReturn(['In Stock' => 'in_stock']);
        $this->listener = new ProductDatagridInventoryStatusListener($enumValueProvider);
    }

    public function testOnPreBuild(): void
    {
        $config = DatagridConfiguration::createNamed('grid-name', []);
        $event = new PreBuild($config, new ParameterBag());

        $this->listener->onPreBuild($event);

        $this->assertEquals(
            [
                'name' => 'grid-name',
                'source' => [
                    'query' => [
                        'select' => [
                            'text.inv_status as inventory_status',
                        ],
                    ],
                ],
                'properties' => [
                    'inventory_status' => [
                        'type' => 'field',
                        'frontend_type' => PropertyInterface::TYPE_STRING,
                    ],
                    'inventory_status_label' => [
                        'type' => 'field',
                        'frontend_type' => PropertyInterface::TYPE_STRING,
                    ],
                ],
            ],
            $config->toArray()
        );
    }

    public function testOnResultAfter(): void
    {
        $record1 = new ResultRecord(['inventory_status' => 'in_stock']);
        $record2 = new ResultRecord(['inventory_status' => 'out_of_stock']);
        $record3 = new ResultRecord(['inventory_status' => '']);
        $record4 = new ResultRecord(['inventory_status' => null]);
        $record5 = new ResultRecord([]);

        $event = new SearchResultAfter(
            $this->createMock(DatagridInterface::class),
            $this->createMock(SearchQueryInterface::class),
            [$record1, $record2, $record3, $record4, $record5]
        );
        $this->listener->onResultAfter($event);

        $this->assertEquals([
            'in_stock',
            'out_of_stock',
            '',
            null,
            null,
        ], [
            $record1->getValue('inventory_status'),
            $record2->getValue('inventory_status'),
            $record3->getValue('inventory_status'),
            $record4->getValue('inventory_status'),
            $record5->getValue('inventory_status'),
        ]);

        $this->assertEquals([
            'In Stock',
            'out_of_stock',
            '',
            null,
            null,
        ], [
            $record1->getValue('inventory_status_label'),
            $record2->getValue('inventory_status_label'),
            $record3->getValue('inventory_status_label'),
            $record4->getValue('inventory_status_label'),
            $record5->getValue('inventory_status_label'),
        ]);
    }
}
