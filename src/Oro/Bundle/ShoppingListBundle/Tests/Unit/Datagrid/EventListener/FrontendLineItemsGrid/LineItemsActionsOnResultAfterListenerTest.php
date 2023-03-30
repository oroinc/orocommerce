<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\EventListener\FrontendLineItemsGrid;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\ShoppingListBundle\Datagrid\EventListener\FrontendLineItemsGrid\LineItemsActionsOnResultAfterListener;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class LineItemsActionsOnResultAfterListenerTest extends TestCase
{
    private AuthorizationCheckerInterface|MockObject $authorizationChecker;

    private LineItemsActionsOnResultAfterListener $listener;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->listener = new LineItemsActionsOnResultAfterListener($this->authorizationChecker);
    }

    public function testOnResultAfterWhenNoRecords(): void
    {
        $this->authorizationChecker
            ->expects($this->never())
            ->method('isGranted');

        $event = new OrmResultAfter($this->getDatagrid(), [], $this->createMock(AbstractQuery::class));
        $this->listener->onResultAfter($event);

        $this->assertCount(0, $event->getRecords());
    }

    /**
     * @dataProvider onResultAfterWhenNoEditPermissionDataProvider
     */
    public function testOnResultAfterWhenNoShoppingList(
        ResultRecordInterface $record,
        ResultRecordInterface $expectedRecord
    ): void {
        $event = $this->createMock(OrmResultAfter::class);
        $event
            ->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($this->getDatagrid());

        $this->authorizationChecker
            ->expects($this->never())
            ->method('isGranted');

        $event
            ->expects($this->once())
            ->method('getRecords')
            ->willReturn([$record]);

        $this->listener->onResultAfter($event);

        $this->assertEquals($expectedRecord, $record);
    }

    /**
     * @dataProvider onResultAfterWhenNoEditPermissionDataProvider
     */
    public function testOnResultAfterWhenEditNotGranted(
        ResultRecordInterface $record,
        ResultRecordInterface $expectedRecord
    ): void {
        $shoppingListId = 1;
        $event = $this->createMock(OrmResultAfter::class);
        $event
            ->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($this->getDatagrid(['shopping_list_id' => $shoppingListId]));

        $query = $this->createMock(AbstractQuery::class);
        $event
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $entityManager = $this->createMock(EntityManager::class);
        $query
            ->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $shoppingList = $this->createMock(ShoppingList::class);
        $entityManager
            ->expects($this->once())
            ->method('find')
            ->with(ShoppingList::class, $shoppingListId)
            ->willReturn($shoppingList);

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('oro_shopping_list_frontend_update', $shoppingList)
            ->willReturn(false);

        $event
            ->expects($this->once())
            ->method('getRecords')
            ->willReturn([$record]);

        $this->listener->onResultAfter($event);

        $this->assertEquals($expectedRecord, $record);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function onResultAfterWhenNoEditPermissionDataProvider(): array
    {
        return [
            'simple row with notes' => [
                'record' => new ResultRecord(['notes' => 'sample note']),
                'expectedRecord' => new ResultRecord(
                    [
                        'notes' => 'sample note',
                        'action_configuration' => [
                            'add_notes' => false,
                            'edit_notes' => false,
                            'update_configurable' => false,
                            'delete' => false,
                        ],
                    ]
                ),
            ],
            'simple row without notes' => [
                'record' => new ResultRecord(['sample_key' => 'sample value']),
                'expectedRecord' => new ResultRecord(
                    [
                        'sample_key' => 'sample value',
                        'action_configuration' => [
                            'add_notes' => false,
                            'edit_notes' => false,
                            'update_configurable' => false,
                            'delete' => false,
                        ],
                    ]
                ),
            ],
            'configurable row with subdata with notes' => [
                'record' => new ResultRecord(['isConfigurable' => true, 'subData' => [['notes' => 'sample notes']]]),
                'expectedRecord' => new ResultRecord(
                    [
                        'isConfigurable' => true,
                        'subData' => [
                            [
                                'notes' => 'sample notes',
                                'action_configuration' => [
                                    'add_notes' => false,
                                    'edit_notes' => false,
                                    'update_configurable' => false,
                                    'delete' => false,
                                ],
                            ],
                        ],
                        'action_configuration' => [
                            'add_notes' => false,
                            'edit_notes' => false,
                            'update_configurable' => false,
                            'delete' => false,
                        ],
                    ]
                ),
            ],
            'configurable row with subdata without notes' => [
                'record' => new ResultRecord(
                    ['isConfigurable' => true, 'subData' => [['sample_key' => 'sample value']]]
                ),
                'expectedRecord' => new ResultRecord(
                    [
                        'isConfigurable' => true,
                        'subData' => [
                            [
                                'sample_key' => 'sample value',
                                'action_configuration' => [
                                    'add_notes' => false,
                                    'edit_notes' => false,
                                    'update_configurable' => false,
                                    'delete' => false,
                                ],
                            ],
                        ],
                        'action_configuration' => [
                            'add_notes' => false,
                            'edit_notes' => false,
                            'update_configurable' => false,
                            'delete' => false,
                        ],
                    ]
                ),
            ],
            'configurable without subdata' => [
                'record' => new ResultRecord(['isConfigurable' => true]),
                'expectedRecord' => new ResultRecord(
                    [
                        'isConfigurable' => true,
                        'subData' => [],
                        'action_configuration' => [
                            'add_notes' => false,
                            'edit_notes' => false,
                            'update_configurable' => false,
                            'delete' => false,
                        ],
                    ]
                ),
            ],
            'configurable with matrix form available' => [
                'record' => new ResultRecord(['isConfigurable' => true, 'isMatrixFormAvailable' => true]),
                'expectedRecord' => new ResultRecord(
                    [
                        'isConfigurable' => true,
                        'isMatrixFormAvailable' => true,
                        'subData' => [],
                        'action_configuration' => [
                            'add_notes' => false,
                            'edit_notes' => false,
                            'update_configurable' => false,
                            'delete' => false,
                        ],
                    ]
                ),
            ],
            'kit with subdata with notes' => [
                'record' => new ResultRecord(['isKit' => true, 'subData' => [['notes' => 'sample notes']]]),
                'expectedRecord' => new ResultRecord(
                    [
                        'isKit' => true,
                        'subData' => [
                            [
                                'notes' => 'sample notes',
                                'action_configuration' => [
                                    'add_notes' => false,
                                    'edit_notes' => false,
                                    'update_configurable' => false,
                                    'delete' => false,
                                ],
                            ],
                        ],
                        'action_configuration' => [
                            'add_notes' => false,
                            'edit_notes' => false,
                            'update_configurable' => false,
                            'delete' => false,
                        ],
                    ]
                ),
            ],
            'kit with subdata without notes' => [
                'record' => new ResultRecord(
                    ['isKit' => true, 'subData' => [['sample_key' => 'sample value']]]
                ),
                'expectedRecord' => new ResultRecord(
                    [
                        'isKit' => true,
                        'subData' => [
                            [
                                'sample_key' => 'sample value',
                                'action_configuration' => [
                                    'add_notes' => false,
                                    'edit_notes' => false,
                                    'update_configurable' => false,
                                    'delete' => false,
                                ],
                            ],
                        ],
                        'action_configuration' => [
                            'add_notes' => false,
                            'edit_notes' => false,
                            'update_configurable' => false,
                            'delete' => false,
                        ],
                    ]
                ),
            ],
        ];
    }

    /**
     * @dataProvider onResultAfterWhenShoppingListDataProvider
     */
    public function testOnResultAfterWhenShoppingList(
        ResultRecordInterface $record,
        ResultRecordInterface $expectedRecord
    ): void {
        $shoppingListId = 1;
        $event = $this->createMock(OrmResultAfter::class);
        $event
            ->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($this->getDatagrid(['shopping_list_id' => $shoppingListId]));

        $query = $this->createMock(AbstractQuery::class);
        $event
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $entityManager = $this->createMock(EntityManager::class);
        $query
            ->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $shoppingList = $this->createMock(ShoppingList::class);
        $entityManager
            ->expects($this->once())
            ->method('find')
            ->with(ShoppingList::class, $shoppingListId)
            ->willReturn($shoppingList);

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('oro_shopping_list_frontend_update', $shoppingList)
            ->willReturn(true);

        $event
            ->expects($this->once())
            ->method('getRecords')
            ->willReturn([$record]);

        $this->listener->onResultAfter($event);

        $this->assertEquals($expectedRecord, $record);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function onResultAfterWhenShoppingListDataProvider(): array
    {
        return [
            'simple row with notes' => [
                'record' => new ResultRecord(['notes' => 'sample note']),
                'expectedRecord' => new ResultRecord(
                    [
                        'notes' => 'sample note',
                        'action_configuration' => [
                            'add_notes' => false,
                            'edit_notes' => false,
                            'update_configurable' => false,
                            'delete' => true,
                        ],
                    ]
                ),
            ],
            'simple row without notes' => [
                'record' => new ResultRecord(['sample_key' => 'sample value']),
                'expectedRecord' => new ResultRecord(
                    [
                        'sample_key' => 'sample value',
                        'action_configuration' => [
                            'add_notes' => true,
                            'edit_notes' => false,
                            'update_configurable' => false,
                            'delete' => true,
                        ],
                    ]
                ),
            ],
            'configurable row with subdata with notes' => [
                'record' => new ResultRecord(['isConfigurable' => true, 'subData' => [['notes' => 'sample notes']]]),
                'expectedRecord' => new ResultRecord(
                    [
                        'isConfigurable' => true,
                        'subData' => [
                            [
                                'notes' => 'sample notes',
                                'action_configuration' => [
                                    'add_notes' => false,
                                    'edit_notes' => false,
                                    'update_configurable' => false,
                                    'delete' => true,
                                ],
                            ],
                        ],
                        'action_configuration' => [
                            'add_notes' => false,
                            'edit_notes' => false,
                            'update_configurable' => false,
                            'delete' => true,
                        ],
                    ]
                ),
            ],
            'configurable row with subdata without notes' => [
                'record' => new ResultRecord(
                    ['isConfigurable' => true, 'subData' => [['sample_key' => 'sample value']]]
                ),
                'expectedRecord' => new ResultRecord(
                    [
                        'isConfigurable' => true,
                        'subData' => [
                            [
                                'sample_key' => 'sample value',
                                'action_configuration' => [
                                    'add_notes' => true,
                                    'edit_notes' => false,
                                    'update_configurable' => false,
                                    'delete' => true,
                                ],
                            ],
                        ],
                        'action_configuration' => [
                            'add_notes' => false,
                            'edit_notes' => false,
                            'update_configurable' => false,
                            'delete' => true,
                        ],
                    ]
                ),
            ],
            'configurable without subdata' => [
                'record' => new ResultRecord(['isConfigurable' => true]),
                'expectedRecord' => new ResultRecord(
                    [
                        'isConfigurable' => true,
                        'subData' => [],
                        'action_configuration' => [
                            'add_notes' => false,
                            'edit_notes' => false,
                            'update_configurable' => false,
                            'delete' => true,
                        ],
                    ]
                ),
            ],
            'configurable with matrix form available' => [
                'record' => new ResultRecord(['isConfigurable' => true, 'isMatrixFormAvailable' => true]),
                'expectedRecord' => new ResultRecord(
                    [
                        'isConfigurable' => true,
                        'isMatrixFormAvailable' => true,
                        'subData' => [],
                        'action_configuration' => [
                            'add_notes' => false,
                            'edit_notes' => false,
                            'update_configurable' => true,
                            'delete' => true,
                        ],
                    ]
                ),
            ],
            'kit with subdata with notes' => [
                'record' => new ResultRecord(['isKit' => true, 'subData' => [['notes' => 'sample notes']]]),
                'expectedRecord' => new ResultRecord(
                    [
                        'isKit' => true,
                        'subData' => [
                            [
                                'notes' => 'sample notes',
                                'action_configuration' => [
                                    'add_notes' => false,
                                    'edit_notes' => false,
                                    'update_configurable' => false,
                                    'delete' => false,
                                ],
                            ],
                        ],
                        'action_configuration' => [
                            'add_notes' => true,
                            'edit_notes' => false,
                            'update_configurable' => false,
                            'delete' => true,
                        ],
                    ]
                ),
            ],
            'kit with subdata without notes' => [
                'record' => new ResultRecord(
                    ['isKit' => true, 'subData' => [['sample_key' => 'sample value']]]
                ),
                'expectedRecord' => new ResultRecord(
                    [
                        'isKit' => true,
                        'subData' => [
                            [
                                'sample_key' => 'sample value',
                                'action_configuration' => [
                                    'add_notes' => false,
                                    'edit_notes' => false,
                                    'update_configurable' => false,
                                    'delete' => false,
                                ],
                            ],
                        ],
                        'action_configuration' => [
                            'add_notes' => true,
                            'edit_notes' => false,
                            'update_configurable' => false,
                            'delete' => true,
                        ],
                    ]
                ),
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
