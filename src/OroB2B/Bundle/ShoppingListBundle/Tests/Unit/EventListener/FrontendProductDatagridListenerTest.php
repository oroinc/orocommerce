<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Query\Expr\Join;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\EventListener\FrontendProductDatagridListener;

class FrontendProductDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    const SHOPPING_LIST_CLASS_NAME = 'OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList';
    const SHOPPING_LIST_LINE_ITEM_CLASS_NAME = 'OroB2B\Bundle\ShoppingListBundle\Entity\LineItem';

    use EntityTrait;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ShoppingListRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var FrontendProductDatagridListener
     */
    protected $listener;

    public function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');

        $this->repository = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new FrontendProductDatagridListener(
            $this->securityFacade,
            $this->registry,
            self::SHOPPING_LIST_CLASS_NAME,
            self::SHOPPING_LIST_LINE_ITEM_CLASS_NAME
        );
    }

    protected function tearDown()
    {
        unset($this->securityFacade, $this->registry, $this->repository, $this->listener);
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
     * @param object|AccountUser|null $accountUser
     * @param array $data
     * @param array $expected
     */
    public function testOnResultAfter($accountUser, array $data, array $expected = [])
    {
        $this->securityFacade->expects($this->once())->method('getLoggedUser')->willReturn($accountUser);

        if ($accountUser instanceof AccountUser) {
            $this->registry->expects($this->once())
                ->method('getRepository')
                ->with(self::SHOPPING_LIST_CLASS_NAME)
                ->willReturn($this->repository);

            /** @var ShoppingList $shoppingList */
            $shoppingList = $this->getEntity(self::SHOPPING_LIST_CLASS_NAME, ['id' => 42]);

            $this->repository->expects($this->once())
                ->method('findAvailableForAccountUser')
                ->with($accountUser)
                ->willReturn($shoppingList);
        } else {
            $this->registry->expects($this->never())->method($this->anything());
        }

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
                'accountUser' => new AccountUser(),
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
                'accountUser' => new \stdClass(),
                'data' => $data
            ],
            [
                'accountUser' => null,
                'data' => $data
            ],
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
