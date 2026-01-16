<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\EventListener\DatagridLineItemsDataValidationListener;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\EventListener\DatagridKitLineItemsDataListener;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderFactory\ProductLineItemsHolderFactory;
use Oro\Bundle\ShoppingListBundle\Mapper\ShoppingListInvalidLineItemsMapper;
use Oro\Bundle\ShoppingListBundle\Provider\InvalidShoppingListLineItemsProvider;
use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListValidationGroupsProvider;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Stub\LineItemStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Contracts\Translation\TranslatorInterface;

class DatagridLineItemsDataValidationListenerTest extends TestCase
{
    private InvalidShoppingListLineItemsProvider&MockObject $invalidShoppingListLineItemsProvider;
    private ShoppingListValidationGroupsProvider&MockObject $validationGroupsProvider;
    private DatagridLineItemsDataValidationListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->invalidShoppingListLineItemsProvider = $this->createMock(InvalidShoppingListLineItemsProvider::class);
        $lineItemsHolderFactory = new ProductLineItemsHolderFactory();

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(static fn (string $id) => $id . '.translated');

        $this->validationGroupsProvider = $this->createMock(ShoppingListValidationGroupsProvider::class);
        $this->validationGroupsProvider->expects(self::any())
            ->method('getAllValidationGroups')
            ->willReturn(['datagrid_line_items_data']);

        $this->listener = new DatagridLineItemsDataValidationListener(
            $this->invalidShoppingListLineItemsProvider,
            $lineItemsHolderFactory,
            $translator,
            $this->validationGroupsProvider
        );
    }

    private function createViolation(string $message): ConstraintViolation
    {
        $violation = $this->createMock(ConstraintViolation::class);
        $violation->expects(self::any())
            ->method('getMessage')
            ->willReturn($message);
        return $violation;
    }

    public function testOnLineItemDataWhenNoLineItems(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);
        $event->expects(self::once())
            ->method('getLineItems')
            ->willReturn([]);
        $event->expects(self::never())
            ->method('setDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenNoViolations(): void
    {
        $lineItem1 = (new LineItemStub())->setId(10);
        $lineItem2 = (new LineItemStub())->setId(20);
        $lineItems = [$lineItem1->getId() => $lineItem1, $lineItem2->getId() => $lineItem2];
        $event = new DatagridLineItemsDataEvent($lineItems, [], $this->createMock(Datagrid::class), []);

        $this->invalidShoppingListLineItemsProvider->expects(self::once())
            ->method('getInvalidItemsViolations')
            ->with($lineItems, null)
            ->willReturn([
                ShoppingListInvalidLineItemsMapper::ERRORS => [],
                ShoppingListInvalidLineItemsMapper::WARNINGS => []
            ]);

        $this->listener->onLineItemData($event);

        $emptyData = [
            ShoppingListInvalidLineItemsMapper::WARNINGS => [],
            ShoppingListInvalidLineItemsMapper::ERRORS => []
        ];
        self::assertEquals(
            [$lineItem1->getId() => $emptyData, $lineItem2->getId() => $emptyData],
            $event->getDataForAllLineItems()
        );
    }

    public function testOnLineItemDataWhenHasViolations(): void
    {
        $lineItem1 = (new LineItemStub())->setId(10);
        $lineItem2 = (new LineItemStub())->setId(20);
        $lineItems = [$lineItem1->getId() => $lineItem1, $lineItem2->getId() => $lineItem2];
        $event = new DatagridLineItemsDataEvent($lineItems, [], $this->createMock(Datagrid::class), []);

        $this->invalidShoppingListLineItemsProvider->expects(self::once())
            ->method('getInvalidItemsViolations')
            ->with($lineItems, null)
            ->willReturn([
                ShoppingListInvalidLineItemsMapper::ERRORS => [
                    20 => [
                        ShoppingListInvalidLineItemsMapper::MESSAGES => [$this->createViolation('error3')],
                        ShoppingListInvalidLineItemsMapper::SUB_DATA => []
                    ]
                ],
                ShoppingListInvalidLineItemsMapper::WARNINGS => [
                    10 => [
                        ShoppingListInvalidLineItemsMapper::MESSAGES => [$this->createViolation('warning1')],
                        ShoppingListInvalidLineItemsMapper::SUB_DATA => []
                    ],
                    20 => [
                        ShoppingListInvalidLineItemsMapper::MESSAGES => [$this->createViolation('warning2')],
                        ShoppingListInvalidLineItemsMapper::SUB_DATA => []
                    ]
                ]
            ]);

        $this->listener->onLineItemData($event);

        self::assertEquals(
            [
                ShoppingListInvalidLineItemsMapper::WARNINGS => ['warning1'],
                ShoppingListInvalidLineItemsMapper::ERRORS => []
            ],
            $event->getDataForLineItem(10)
        );
        self::assertEquals(
            [
                ShoppingListInvalidLineItemsMapper::WARNINGS => ['warning2'],
                ShoppingListInvalidLineItemsMapper::ERRORS => ['error3']
            ],
            $event->getDataForLineItem(20)
        );
    }

    public function testOnLineItemDataWhenHasNoViolationsInKitItem(): void
    {
        $lineItem1 = (new LineItemStub())->setId(10);
        $lineItem2 = (new LineItemStub())->setId(20);
        $lineItems = [$lineItem1->getId() => $lineItem1, $lineItem2->getId() => $lineItem2];
        $event = new DatagridLineItemsDataEvent(
            $lineItems,
            [
                $lineItem2->getId() => [
                    DatagridKitLineItemsDataListener::IS_KIT => true,
                    DatagridKitLineItemsDataListener::SUB_DATA => []
                ]
            ],
            $this->createMock(Datagrid::class),
            []
        );

        $this->invalidShoppingListLineItemsProvider->expects(self::once())
            ->method('getInvalidItemsViolations')
            ->with($lineItems, null)
            ->willReturn([
                ShoppingListInvalidLineItemsMapper::ERRORS => [],
                ShoppingListInvalidLineItemsMapper::WARNINGS => [
                    10 => [
                        ShoppingListInvalidLineItemsMapper::MESSAGES => [$this->createViolation('warning1')],
                        ShoppingListInvalidLineItemsMapper::SUB_DATA => []
                    ]
                ]
            ]);

        $this->listener->onLineItemData($event);

        self::assertEquals(
            [
                ShoppingListInvalidLineItemsMapper::WARNINGS => ['warning1'],
                ShoppingListInvalidLineItemsMapper::ERRORS => []
            ],
            $event->getDataForLineItem(10)
        );
        self::assertEquals(
            [
                DatagridKitLineItemsDataListener::IS_KIT => true,
                DatagridKitLineItemsDataListener::SUB_DATA => [],
                ShoppingListInvalidLineItemsMapper::WARNINGS => [],
                ShoppingListInvalidLineItemsMapper::ERRORS => [],
                DatagridLineItemsDataValidationListener::KIT_HAS_GENERAL_ERROR => false
            ],
            $event->getDataForLineItem(20)
        );
    }

    public function testOnLineItemDataWhenHasViolationsInKitItem(): void
    {
        $lineItem1 = (new LineItemStub())->setId(10);
        $lineItem2 = (new LineItemStub())->setId(20);
        $lineItems = [$lineItem1->getId() => $lineItem1, $lineItem2->getId() => $lineItem2];
        $event = new DatagridLineItemsDataEvent(
            $lineItems,
            [
                $lineItem2->getId() => [
                    DatagridKitLineItemsDataListener::IS_KIT => true,
                    DatagridKitLineItemsDataListener::SUB_DATA => [
                        0 => [ShoppingListInvalidLineItemsMapper::ERRORS => ['sample_error1']]
                    ]
                ]
            ],
            $this->createMock(Datagrid::class),
            []
        );

        // Note: Since LineItemStub doesn't have kit items, createKitItemIdToIndexMap will return empty map
        // So kit item violations won't be added to SUB_DATA, only existing errors will remain
        $this->invalidShoppingListLineItemsProvider->expects(self::once())
            ->method('getInvalidItemsViolations')
            ->with($lineItems, null)
            ->willReturn([
                ShoppingListInvalidLineItemsMapper::ERRORS => [
                    20 => [
                        ShoppingListInvalidLineItemsMapper::MESSAGES => [],
                        ShoppingListInvalidLineItemsMapper::SUB_DATA => [
                            30 => [
                                ShoppingListInvalidLineItemsMapper::MESSAGES => [$this->createViolation('kit_error1')]
                            ]
                        ]
                    ]
                ],
                ShoppingListInvalidLineItemsMapper::WARNINGS => [
                    10 => [
                        ShoppingListInvalidLineItemsMapper::MESSAGES => [$this->createViolation('warning1')],
                        ShoppingListInvalidLineItemsMapper::SUB_DATA => []
                    ]
                ]
            ]);

        $this->listener->onLineItemData($event);

        self::assertEquals(
            [
                ShoppingListInvalidLineItemsMapper::WARNINGS => ['warning1'],
                ShoppingListInvalidLineItemsMapper::ERRORS => []
            ],
            $event->getDataForLineItem(10)
        );
        // Kit item violations won't be added because LineItemStub doesn't have kit items
        // But KIT_HAS_GENERAL_ERROR will be true because sample_error1 exists in SUB_DATA
        self::assertEquals(
            [
                DatagridKitLineItemsDataListener::IS_KIT => true,
                DatagridKitLineItemsDataListener::SUB_DATA => [
                    0 => [
                        ShoppingListInvalidLineItemsMapper::ERRORS => ['sample_error1'],
                        ShoppingListInvalidLineItemsMapper::WARNINGS => []
                    ]
                ],
                ShoppingListInvalidLineItemsMapper::WARNINGS => [
                    'oro.shoppinglist.product_kit_line_item.general_error.message.translated'
                ],
                ShoppingListInvalidLineItemsMapper::ERRORS => [],
                DatagridLineItemsDataValidationListener::KIT_HAS_GENERAL_ERROR => true
            ],
            $event->getDataForLineItem(20)
        );
    }

    public function testOnLineItemDataWhenHasNoViolationsInKitItemButHasGeneralError(): void
    {
        $lineItem1 = (new LineItemStub())->setId(10);
        $lineItem2 = (new LineItemStub())->setId(20);
        $lineItems = [$lineItem1->getId() => $lineItem1, $lineItem2->getId() => $lineItem2];
        $event = new DatagridLineItemsDataEvent(
            $lineItems,
            [
                $lineItem2->getId() => [
                    DatagridKitLineItemsDataListener::IS_KIT => true,
                    DatagridKitLineItemsDataListener::SUB_DATA => [],
                    DatagridLineItemsDataValidationListener::KIT_HAS_GENERAL_ERROR => true
                ]
            ],
            $this->createMock(Datagrid::class),
            []
        );

        $this->invalidShoppingListLineItemsProvider->expects(self::once())
            ->method('getInvalidItemsViolations')
            ->with($lineItems, null)
            ->willReturn([
                ShoppingListInvalidLineItemsMapper::ERRORS => [],
                ShoppingListInvalidLineItemsMapper::WARNINGS => [
                    10 => [
                        ShoppingListInvalidLineItemsMapper::MESSAGES => [$this->createViolation('warning1')],
                        ShoppingListInvalidLineItemsMapper::SUB_DATA => []
                    ]
                ]
            ]);

        $this->listener->onLineItemData($event);

        self::assertEquals(
            [
                ShoppingListInvalidLineItemsMapper::WARNINGS => ['warning1'],
                ShoppingListInvalidLineItemsMapper::ERRORS => []
            ],
            $event->getDataForLineItem(10)
        );
        self::assertEquals(
            [
                DatagridKitLineItemsDataListener::IS_KIT => true,
                DatagridKitLineItemsDataListener::SUB_DATA => [],
                ShoppingListInvalidLineItemsMapper::WARNINGS => [
                    'oro.shoppinglist.product_kit_line_item.general_error.message.translated'
                ],
                ShoppingListInvalidLineItemsMapper::ERRORS => [],
                DatagridLineItemsDataValidationListener::KIT_HAS_GENERAL_ERROR => true
            ],
            $event->getDataForLineItem(20)
        );
    }
}
