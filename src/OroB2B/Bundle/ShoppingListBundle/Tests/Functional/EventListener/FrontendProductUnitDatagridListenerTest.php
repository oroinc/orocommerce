<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;

use OroB2B\Bundle\ShoppingListBundle\EventListener\FrontendProductUnitDatagridListener;

/**
 * @dbIsolation
 */
class FrontendProductUnitDatagridListenerTest extends WebTestCase
{
    /**
     * @var FrontendProductUnitDatagridListener
     */
    protected $listener;

    public function setUp()
    {
        $this->initClient();

        $this->loadFixtures(['OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions']);

        $this->listener = $this->getContainer()
            ->get('orob2b_shopping_list.event_listener.frontend_product_unit_datagrid');
    }

    /**
     * @dataProvider onResultAfterDataProvider
     * @param array $productReferences
     * @param array $expectedRecords
     */
    public function testOnResultAfter(array $productReferences, array $expectedRecords)
    {
        /** @var DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        /** @var OrmResultAfter $event */
        $event = new OrmResultAfter($datagrid, $this->productReferencesToResultRecords($productReferences));
        $this->listener->onResultAfter($event);
        $this->assertResultRecords($expectedRecords, $event->getRecords());
    }

    /**
     * @return array
     */
    public function onResultAfterDataProvider()
    {
        return [
            [
                'productReferences' => [
                    'product.1',
                    'product.2',
                    'product.3',
                    'product.4',
                    'product.5',
                ],
                'expectedRecords' => [
                    [
                        'id' => 'product.1',
                        'units' => [
                            'liter' => 'orob2b.product_unit.liter.label.full',
                            'bottle' => 'orob2b.product_unit.bottle.label.full',
                        ],
                    ],
                    [
                        'id' => 'product.2',
                        'units' => [
                            'liter' => 'orob2b.product_unit.liter.label.full',
                            'bottle' => 'orob2b.product_unit.bottle.label.full',
                            'box' => 'orob2b.product_unit.box.label.full',
                        ],
                    ],
                    [
                        'id' => 'product.3',
                        'units' => [
                            'liter' => 'orob2b.product_unit.liter.label.full',
                        ],
                    ],
                    [
                        'id' => 'product.4',
                        'units' => ['box' => 'orob2b.product_unit.box.label.full'],
                    ],
                    [
                        'id' => 'product.5',
                        'units' => ['box' => 'orob2b.product_unit.box.label.full'],
                    ],
                ],
            ]
        ];
    }

    /**
     * @param array $productReferences
     * @return ResultRecord[]
     */
    protected function productReferencesToResultRecords(array $productReferences)
    {
        return array_map(function ($productReference) {
            return new ResultRecord(['id' => $this->getReference($productReference)->getId()]);
        }, $productReferences);
    }

    /**
     * @param array $expectedRecordsData
     * @param ResultRecord[] $actualRecords
     * @return ResultRecord[]
     */
    protected function assertResultRecords(array $expectedRecordsData, array $actualRecords)
    {
        $this->assertCount(count($expectedRecordsData), $actualRecords);
        foreach ($expectedRecordsData as $expectedRecord) {
            $actualRecord = current($actualRecords);
            $this->assertEquals($this->getReference($expectedRecord['id'])->getId(), $actualRecord->getValue('id'));
            $this->assertEquals($expectedRecord['units'], $actualRecord->getValue('units'));
            next($actualRecords);
        }
    }
}
