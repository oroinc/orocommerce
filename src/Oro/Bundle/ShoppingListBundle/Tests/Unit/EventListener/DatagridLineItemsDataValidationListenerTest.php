<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\EventListener\DatagridKitLineItemsDataListener;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderDTO;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderFactory\ProductLineItemsHolderFactory;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Bundle\ShoppingListBundle\EventListener\DatagridLineItemsDataValidationListener;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Stub\LineItemStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DatagridLineItemsDataValidationListenerTest extends TestCase
{
    private ValidatorInterface|MockObject $validator;

    private DatagridLineItemsDataValidationListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $lineItemsHolderFactory = new ProductLineItemsHolderFactory();

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(static fn (string $id) => $id . '.translated');

        $this->listener = new DatagridLineItemsDataValidationListener(
            $this->validator,
            $lineItemsHolderFactory,
            $translator
        );
    }

    public function testOnLineItemDataWhenNoLineItems(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);

        $event
            ->expects(self::once())
            ->method('getLineItems')
            ->willReturn([]);

        $event
            ->expects(self::never())
            ->method('setDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenNoViolations(): void
    {
        $lineItem1 = (new LineItemStub())->setId(10);
        $lineItem2 = (new LineItemStub())->setId(20);
        $lineItems = [$lineItem1->getId() => $lineItem1, $lineItem2->getId() => $lineItem2];
        $event = new DatagridLineItemsDataEvent($lineItems, [], $this->createMock(Datagrid::class), []);

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with(
                (new ProductLineItemsHolderDTO())->setLineItems(new ArrayCollection($lineItems)),
                null,
                [new GroupSequence(['Default', 'datagrid_line_items_data'])]
            )
            ->willReturn(new ConstraintViolationList());

        $this->listener->onLineItemData($event);

        $emptyData = [
            DatagridLineItemsDataValidationListener::WARNINGS => [],
            DatagridLineItemsDataValidationListener::ERRORS => [],
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
        $lineItemsHolder = (new ProductLineItemsHolderDTO())->setLineItems(new ArrayCollection($lineItems));
        $constraintViolationList = new ConstraintViolationList();
        $constraintViolationList->add($this->createViolation('warning1', 'warning', $lineItem1, $lineItemsHolder));
        $constraintViolationList->add($this->createViolation('warning2', 'warning', $lineItem2, $lineItemsHolder));
        $constraintViolationList->add($this->createViolation('error3', 'error', $lineItem2, $lineItemsHolder));

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($lineItemsHolder, null, [new GroupSequence(['Default', 'datagrid_line_items_data'])])
            ->willReturn($constraintViolationList);

        $this->listener->onLineItemData($event);

        self::assertEquals(
            [
                DatagridLineItemsDataValidationListener::WARNINGS => ['warning1'],
                DatagridLineItemsDataValidationListener::ERRORS => [],
            ],
            $event->getDataForLineItem(10)
        );
        self::assertEquals(
            [
                DatagridLineItemsDataValidationListener::WARNINGS => ['warning2'],
                DatagridLineItemsDataValidationListener::ERRORS => ['error3'],
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
                    DatagridKitLineItemsDataListener::SUB_DATA => [],
                ],
            ],
            $this->createMock(Datagrid::class),
            []
        );
        $lineItemsHolder = (new ProductLineItemsHolderDTO())->setLineItems(new ArrayCollection($lineItems));
        $constraintViolationList = new ConstraintViolationList();
        $constraintViolationList->add($this->createViolation('warning1', 'warning', $lineItem1, $lineItemsHolder));

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($lineItemsHolder, null, [new GroupSequence(['Default', 'datagrid_line_items_data'])])
            ->willReturn($constraintViolationList);

        $this->listener->onLineItemData($event);

        self::assertEquals(
            [
                DatagridLineItemsDataValidationListener::WARNINGS => ['warning1'],
                DatagridLineItemsDataValidationListener::ERRORS => [],
            ],
            $event->getDataForLineItem(10)
        );
        self::assertEquals(
            [
                DatagridKitLineItemsDataListener::IS_KIT => true,
                DatagridKitLineItemsDataListener::SUB_DATA => [],
                DatagridLineItemsDataValidationListener::WARNINGS => [],
                DatagridLineItemsDataValidationListener::ERRORS => [],
                DatagridLineItemsDataValidationListener::KIT_HAS_GENERAL_ERROR => false,
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
                        [DatagridLineItemsDataValidationListener::ERRORS => ['sample_error1']],
                    ],
                ],
            ],
            $this->createMock(Datagrid::class),
            []
        );
        $lineItemsHolder = (new ProductLineItemsHolderDTO())->setLineItems(new ArrayCollection($lineItems));
        $constraintViolationList = new ConstraintViolationList();
        $constraintViolationList->add($this->createViolation('warning1', 'warning', $lineItem1, $lineItemsHolder));

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($lineItemsHolder, null, [new GroupSequence(['Default', 'datagrid_line_items_data'])])
            ->willReturn($constraintViolationList);

        $this->listener->onLineItemData($event);

        self::assertEquals(
            [
                DatagridLineItemsDataValidationListener::WARNINGS => ['warning1'],
                DatagridLineItemsDataValidationListener::ERRORS => [],
            ],
            $event->getDataForLineItem(10)
        );
        self::assertEquals(
            [
                DatagridKitLineItemsDataListener::IS_KIT => true,
                DatagridKitLineItemsDataListener::SUB_DATA => [
                    [DatagridLineItemsDataValidationListener::ERRORS => ['sample_error1']],
                ],
                DatagridLineItemsDataValidationListener::WARNINGS => [],
                DatagridLineItemsDataValidationListener::ERRORS => [
                    'oro.shoppinglist.product_kit_line_item.general_error.message.translated',
                ],
                DatagridLineItemsDataValidationListener::KIT_HAS_GENERAL_ERROR => true,
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
                    DatagridLineItemsDataValidationListener::KIT_HAS_GENERAL_ERROR => true,
                ],
            ],
            $this->createMock(Datagrid::class),
            []
        );
        $lineItemsHolder = (new ProductLineItemsHolderDTO())->setLineItems(new ArrayCollection($lineItems));
        $constraintViolationList = new ConstraintViolationList();
        $constraintViolationList->add($this->createViolation('warning1', 'warning', $lineItem1, $lineItemsHolder));

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($lineItemsHolder, null, [new GroupSequence(['Default', 'datagrid_line_items_data'])])
            ->willReturn($constraintViolationList);

        $this->listener->onLineItemData($event);

        self::assertEquals(
            [
                DatagridLineItemsDataValidationListener::WARNINGS => ['warning1'],
                DatagridLineItemsDataValidationListener::ERRORS => [],
            ],
            $event->getDataForLineItem(10)
        );
        self::assertEquals(
            [
                DatagridKitLineItemsDataListener::IS_KIT => true,
                DatagridKitLineItemsDataListener::SUB_DATA => [],
                DatagridLineItemsDataValidationListener::WARNINGS => [],
                DatagridLineItemsDataValidationListener::ERRORS => [
                    'oro.shoppinglist.product_kit_line_item.general_error.message.translated',
                ],
                DatagridLineItemsDataValidationListener::KIT_HAS_GENERAL_ERROR => true,
            ],
            $event->getDataForLineItem(20)
        );
    }

    private function createConstraint(string $severity): Constraint
    {
        return new NotNull([], null, null, ['severity' => $severity]);
    }

    private function createViolation(
        string $message,
        string $severity,
        ProductLineItemInterface $lineItem,
        ProductLineItemsHolderInterface $lineItemsHolder
    ): ConstraintViolation {
        return new ConstraintViolation(
            $message,
            null,
            [],
            $lineItemsHolder,
            'lineItems[' . $lineItemsHolder->getLineItems()->indexOf($lineItem) . ']',
            $lineItem,
            null,
            null,
            $this->createConstraint($severity)
        );
    }
}
