<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\EventListener\FrontendProductDatagridListener;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class FrontendProductDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const SHOPPING_LIST_LINE_ITEM_CLASS_NAME = 'OroB2B\Bundle\ShoppingListBundle\Entity\LineItem';

    /** @var ShoppingListManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $shoppingListManager;

    /** @var FrontendProductDatagridListener */
    protected $listener;

    public function setUp()
    {
        $this->shoppingListManager = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new FrontendProductDatagridListener(
            $this->shoppingListManager,
            self::SHOPPING_LIST_LINE_ITEM_CLASS_NAME
        );
    }

    protected function tearDown()
    {
        unset($this->shoppingListManager, $this->listener);
    }

    public function testOnPreBuild()
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
                            "GROUP_CONCAT(CONCAT(IDENTITY(shoppingListLineItem.shoppingList), '{blk}', " .
                            "IDENTITY(shoppingListLineItem.unit), '{blk}', shoppingListLineItem.quantity) " .
                            "SEPARATOR '{unt}') as current_shopping_list_line_items",
                        ],
                        'join' => [
                            'left' => [
                                [
                                    'join' => self::SHOPPING_LIST_LINE_ITEM_CLASS_NAME,
                                    'alias' => 'shoppingListLineItem',
                                    'conditionType' => Join::WITH,
                                    'condition' => 'product.id = IDENTITY(shoppingListLineItem.product)'
                                ]
                            ],
                        ],
                    ],
                ],
                'properties' => [
                    FrontendProductDatagridListener::COLUMN_LINE_ITEMS => [
                        'type' => 'field',
                        'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY
                    ]
                ],
            ],
            $config->toArray()
        );
    }

    /**
     * @dataProvider onResultAfterDataProvider
     *
     * @param ShoppingList|null $shoppingList
     * @param array $data
     * @param array $expected
     */
    public function testOnResultAfter($shoppingList, array $data, array $expected = [])
    {
        $this->shoppingListManager->expects($this->once())->method('getCurrent')->willReturn($shoppingList);

        $records = array_map(
            function ($record) {
                return new ResultRecord($record);
            },
            $data
        );

        /** @var OrmResultAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\OrmResultAfter')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())->method('getRecords')->willReturn($records);

        $this->listener->onResultAfter($event);

        foreach ($expected as $key => $expectedRecord) {
            /** @var ResultRecord $record */
            $record = $records[$key];

            $this->assertEquals(
                $expectedRecord[FrontendProductDatagridListener::COLUMN_LINE_ITEMS],
                $record->getValue(FrontendProductDatagridListener::COLUMN_LINE_ITEMS)
            );
        }
    }

    /**
     * @return array
     */
    public function onResultAfterDataProvider()
    {
        $data = [
            [
                FrontendProductDatagridListener::COLUMN_LINE_ITEMS => implode(
                    FrontendProductDatagridListener::DATA_SEPARATOR,
                    [
                        $this->getResultString(42, 'item', 100),
                        $this->getResultString(5, 'item', 50),
                        $this->getResultString(20, 'kg', 50),
                        $this->getResultString(42, 'kg', 200),
                        $this->getResultString(35, 'hour', 50),
                        $this->getResultString(42, 'hour', 300)
                    ]
                )
            ],
            [
                FrontendProductDatagridListener::COLUMN_LINE_ITEMS => implode(
                    FrontendProductDatagridListener::DATA_SEPARATOR,
                    [
                        $this->getResultString(5, 'item', 50),
                        $this->getResultString(20, 'kg', 50),
                        $this->getResultString(35, 'hour', 50)
                    ]
                )
            ],
            [
                FrontendProductDatagridListener::COLUMN_LINE_ITEMS => ''
            ]
        ];

        return [
            [
                'shoppingList' => $this->getEntity(
                    'OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList',
                    ['id' => 42]
                ),
                'data' => $data,
                'expected' => [
                    [
                        FrontendProductDatagridListener::COLUMN_LINE_ITEMS => [
                            'item' => '100',
                            'kg' => '200',
                            'hour' => '300'
                        ]
                    ],
                    [FrontendProductDatagridListener::COLUMN_LINE_ITEMS => []],
                    [FrontendProductDatagridListener::COLUMN_LINE_ITEMS => []]
                ]
            ],
            [
                'shoppingList' => null,
                'data' => $data,
                'expected' => [
                    [FrontendProductDatagridListener::COLUMN_LINE_ITEMS => []],
                    [FrontendProductDatagridListener::COLUMN_LINE_ITEMS => []],
                    [FrontendProductDatagridListener::COLUMN_LINE_ITEMS => []]
                ]
            ]
        ];
    }

    /**
     * @param int $shoppingListId
     * @param string $productUnitCode
     * @param int $quantity
     * @return string
     */
    protected function getResultString($shoppingListId, $productUnitCode, $quantity)
    {
        return sprintf(
            '%d%s%s%s%d',
            $shoppingListId,
            FrontendProductDatagridListener::BLOCK_SEPARATOR,
            $productUnitCode,
            FrontendProductDatagridListener::BLOCK_SEPARATOR,
            $quantity
        );
    }
}
