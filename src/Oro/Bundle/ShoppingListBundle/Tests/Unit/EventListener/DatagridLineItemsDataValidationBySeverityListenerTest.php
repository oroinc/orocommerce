<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\EventListener\DatagridKitLineItemsDataListener;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderDTO;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderFactory\ProductLineItemsHolderFactory;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Bundle\ShoppingListBundle\EventListener\DatagridLineItemsDataValidationBySeverityListener;
use Oro\Bundle\ShoppingListBundle\EventListener\DatagridLineItemsDataValidationListener;
use Oro\Bundle\ShoppingListBundle\Mapper\ShoppingListInvalidLineItemsMapper;
use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListValidationGroupsProvider;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Stub\LineItemStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DatagridLineItemsDataValidationBySeverityListenerTest extends TestCase
{
    private ValidatorInterface&MockObject $validator;
    private ShoppingListValidationGroupsProvider&MockObject $validationGroupsProvider;
    private FeatureChecker&MockObject $featureChecker;
    private DatagridLineItemsDataValidationBySeverityListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $lineItemsHolderFactory = new ProductLineItemsHolderFactory();

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(static fn (string $id) => $id . '.translated');

        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->validationGroupsProvider = $this->createMock(ShoppingListValidationGroupsProvider::class);

        $this->listener = new DatagridLineItemsDataValidationBySeverityListener(
            $this->validator,
            $lineItemsHolderFactory,
            $translator,
            new ShoppingListInvalidLineItemsMapper(),
            $this->validationGroupsProvider
        );

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature');
    }

    private function createConstraint(string $severity, array $groups = []): Constraint
    {
        return new NotNull([], null, $groups, ['severity' => $severity]);
    }

    private function createViolation(
        string $message,
        string $severity,
        ProductLineItemInterface $lineItem,
        ProductLineItemsHolderInterface $lineItemsHolder,
        array $groups = []
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
            $this->createConstraint($severity, $groups)
        );
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

    public function testOnLineItemDataWhenNoContinueAction(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);
        $event->expects(self::once())
            ->method('getLineItems')
            ->willReturn([$this->createMock(ProductLineItemInterface::class)]);
        $event->expects(self::never())
            ->method('setDataForLineItem');
        $this->validationGroupsProvider
            ->expects(self::once())
            ->method('getAllValidationGroups')
            ->willReturn([]);

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenNoViolationsWithEnabledFeature(): void
    {
        $this->validationGroupsProvider
            ->expects(self::any())
            ->method('getAllValidationGroups')
            ->willReturn(['datagrid_line_items_data_for_checkout', 'datagrid_line_items_data_for_rfq']);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $lineItem1 = (new LineItemStub())->setId(10);
        $lineItem2 = (new LineItemStub())->setId(20);
        $lineItems = [$lineItem1->getId() => $lineItem1, $lineItem2->getId() => $lineItem2];
        $event = new DatagridLineItemsDataEvent($lineItems, [], $this->createMock(Datagrid::class), []);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with(
                (new ProductLineItemsHolderDTO())->setLineItems(new ArrayCollection($lineItems)),
                null,
                ['datagrid_line_items_data_for_checkout', 'datagrid_line_items_data_for_rfq']
            )
            ->willReturn(new ConstraintViolationList());

        $this->listener->onLineItemData($event);

        $emptyData = [
            DatagridLineItemsDataValidationListener::WARNINGS => [],
            DatagridLineItemsDataValidationListener::ERRORS => []
        ];
        self::assertEquals(
            [$lineItem1->getId() => $emptyData, $lineItem2->getId() => $emptyData],
            $event->getDataForAllLineItems()
        );
    }

    public function testOnLineItemDataWhenNoViolationsWithDisabledFeature(): void
    {
        $this->validationGroupsProvider
            ->expects(self::once())
            ->method('getAllValidationGroups')
            ->willReturn(['group']);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(false);

        $lineItem1 = (new LineItemStub())->setId(10);
        $lineItem2 = (new LineItemStub())->setId(20);
        $lineItems = [$lineItem1->getId() => $lineItem1, $lineItem2->getId() => $lineItem2];
        $event = new DatagridLineItemsDataEvent($lineItems, [], $this->createMock(Datagrid::class), []);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with(
                (new ProductLineItemsHolderDTO())->setLineItems(new ArrayCollection($lineItems)),
                null,
                ['Default', 'datagrid_line_items_data']
            )
            ->willReturn(new ConstraintViolationList());

        $this->listener->onLineItemData($event);

        $emptyData = [
            DatagridLineItemsDataValidationListener::WARNINGS => [],
            DatagridLineItemsDataValidationListener::ERRORS => []
        ];
        self::assertEquals(
            [$lineItem1->getId() => $emptyData, $lineItem2->getId() => $emptyData],
            $event->getDataForAllLineItems()
        );
    }

    public function testOnLineItemDataWhenHasViolationsWithEnabledFeature(): void
    {
        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $this->validationGroupsProvider
            ->expects(self::any())
            ->method('getAllValidationGroups')
            ->willReturn(['datagrid_line_items_data_for_checkout', 'datagrid_line_items_data_for_rfq']);

        $lineItem1 = (new LineItemStub())->setId(10);
        $lineItem2 = (new LineItemStub())->setId(20);
        $lineItems = [$lineItem1->getId() => $lineItem1, $lineItem2->getId() => $lineItem2];
        $event = new DatagridLineItemsDataEvent($lineItems, [], $this->createMock(Datagrid::class), []);
        $lineItemsHolder = (new ProductLineItemsHolderDTO())->setLineItems(new ArrayCollection($lineItems));
        $constraintViolationList = new ConstraintViolationList();
        $constraintViolationList->add($this->createViolation('warning1', 'warning', $lineItem1, $lineItemsHolder));
        $constraintViolationList->add($this->createViolation('warning2', 'warning', $lineItem2, $lineItemsHolder));
        $constraintViolationList->add($this->createViolation(
            'error3',
            'error',
            $lineItem2,
            $lineItemsHolder,
            ['datagrid_line_items_data_for_checkout', 'datagrid_line_items_data_for_rfq']
        ));

        $this->validator->expects(self::once())
            ->method('validate')
            ->with(
                $lineItemsHolder,
                null,
                ['datagrid_line_items_data_for_checkout', 'datagrid_line_items_data_for_rfq']
            )
            ->willReturn($constraintViolationList);

        $this->listener->onLineItemData($event);

        self::assertEquals(
            [
                DatagridLineItemsDataValidationListener::WARNINGS => ['warning1'],
                DatagridLineItemsDataValidationListener::ERRORS => []
            ],
            $event->getDataForLineItem(10)
        );
        self::assertEquals(
            [
                DatagridLineItemsDataValidationListener::WARNINGS => ['warning2'],
                DatagridLineItemsDataValidationListener::ERRORS => ['error3']
            ],
            $event->getDataForLineItem(20)
        );
    }

    public function testOnLineItemDataWhenHasViolationsWithDisabledFeature(): void
    {
        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(false);

        $this->validationGroupsProvider
            ->expects(self::once())
            ->method('getAllValidationGroups')
            ->willReturn(['datagrid_line_items_data_for_checkout', 'datagrid_line_items_data_for_rfq']);

        $lineItem1 = (new LineItemStub())->setId(10);
        $lineItem2 = (new LineItemStub())->setId(20);
        $lineItems = [$lineItem1->getId() => $lineItem1, $lineItem2->getId() => $lineItem2];
        $event = new DatagridLineItemsDataEvent($lineItems, [], $this->createMock(Datagrid::class), []);
        $lineItemsHolder = (new ProductLineItemsHolderDTO())->setLineItems(new ArrayCollection($lineItems));
        $constraintViolationList = new ConstraintViolationList();
        $constraintViolationList->add($this->createViolation('warning1', 'warning', $lineItem1, $lineItemsHolder));
        $constraintViolationList->add($this->createViolation('warning2', 'warning', $lineItem2, $lineItemsHolder));
        $constraintViolationList->add($this->createViolation(
            'error3',
            'error',
            $lineItem2,
            $lineItemsHolder,
            ['datagrid_line_items_data']
        ));

        $this->validator->expects(self::once())
            ->method('validate')
            ->with(
                $lineItemsHolder,
                null,
                ['Default', 'datagrid_line_items_data']
            )
            ->willReturn($constraintViolationList);

        $this->listener->onLineItemData($event);

        self::assertEquals(
            [
                DatagridLineItemsDataValidationListener::WARNINGS => ['warning1'],
                DatagridLineItemsDataValidationListener::ERRORS => []
            ],
            $event->getDataForLineItem(10)
        );
        self::assertEquals(
            [
                DatagridLineItemsDataValidationListener::WARNINGS => ['warning2', 'error3'],
                DatagridLineItemsDataValidationListener::ERRORS => []
            ],
            $event->getDataForLineItem(20)
        );
    }

    public function testOnLineItemDataWhenHasNoViolationsInKitItem(): void
    {
        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $this->validationGroupsProvider
            ->expects(self::once())
            ->method('getAllValidationGroups')
            ->willReturn(['datagrid_line_items_data_for_checkout', 'datagrid_line_items_data_for_rfq']);

        $this->validationGroupsProvider
            ->expects(self::once())
            ->method('getValidationGroupByType')
            ->willReturn('datagrid_line_items_data_for_checkout');

        $datagrid = $this->createMock(Datagrid::class);
        $datagrid->expects(self::once())
            ->method('getParameters')
            ->willReturn(new ParameterBag(['triggered_by' => 'checkout']));
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
            $datagrid,
            []
        );
        $lineItemsHolder = (new ProductLineItemsHolderDTO())->setLineItems(new ArrayCollection($lineItems));
        $constraintViolationList = new ConstraintViolationList();
        $constraintViolationList->add($this->createViolation('warning1', 'warning', $lineItem1, $lineItemsHolder));

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($lineItemsHolder, null, ['datagrid_line_items_data_for_checkout'])
            ->willReturn($constraintViolationList);

        $this->listener->onLineItemData($event);

        self::assertEquals(
            [
                DatagridLineItemsDataValidationListener::WARNINGS => ['warning1'],
                DatagridLineItemsDataValidationListener::ERRORS => []
            ],
            $event->getDataForLineItem(10)
        );
        self::assertEquals(
            [
                DatagridKitLineItemsDataListener::IS_KIT => true,
                DatagridKitLineItemsDataListener::SUB_DATA => [],
                DatagridLineItemsDataValidationListener::WARNINGS => [],
                DatagridLineItemsDataValidationListener::ERRORS => [],
                DatagridLineItemsDataValidationListener::KIT_HAS_GENERAL_ERROR => false
            ],
            $event->getDataForLineItem(20)
        );
    }

    public function testOnLineItemDataWhenHasViolationsInKitItem(): void
    {
        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $this->validationGroupsProvider
            ->expects(self::any())
            ->method('getAllValidationGroups')
            ->willReturn(['datagrid_line_items_data_for_checkout', 'datagrid_line_items_data_for_rfq']);

        $lineItem1 = (new LineItemStub())->setId(10);
        $lineItem2 = (new LineItemStub())->setId(20);
        $lineItems = [$lineItem1->getId() => $lineItem1, $lineItem2->getId() => $lineItem2];
        $event = new DatagridLineItemsDataEvent(
            $lineItems,
            [
                $lineItem2->getId() => [
                    DatagridKitLineItemsDataListener::IS_KIT => true,
                    DatagridKitLineItemsDataListener::SUB_DATA => [
                        [DatagridLineItemsDataValidationListener::ERRORS => ['sample_error1']]
                    ]
                ]
            ],
            $this->createMock(Datagrid::class),
            []
        );
        $lineItemsHolder = (new ProductLineItemsHolderDTO())->setLineItems(new ArrayCollection($lineItems));
        $constraintViolationList = new ConstraintViolationList();
        $constraintViolationList->add($this->createViolation('warning1', 'warning', $lineItem1, $lineItemsHolder));

        $this->validator->expects(self::once())
            ->method('validate')
            ->with(
                $lineItemsHolder,
                null,
                ['datagrid_line_items_data_for_checkout', 'datagrid_line_items_data_for_rfq']
            )
            ->willReturn($constraintViolationList);

        $this->listener->onLineItemData($event);

        self::assertEquals(
            [
                DatagridLineItemsDataValidationListener::WARNINGS => ['warning1'],
                DatagridLineItemsDataValidationListener::ERRORS => []
            ],
            $event->getDataForLineItem(10)
        );
        self::assertEquals(
            [
                DatagridKitLineItemsDataListener::IS_KIT => true,
                DatagridKitLineItemsDataListener::SUB_DATA => [
                    [DatagridLineItemsDataValidationListener::ERRORS => ['sample_error1']]
                ],
                DatagridLineItemsDataValidationListener::WARNINGS => [
                    'oro.shoppinglist.product_kit_line_item.general_error.message.translated'
                ],
                DatagridLineItemsDataValidationListener::ERRORS => [],
                DatagridLineItemsDataValidationListener::KIT_HAS_GENERAL_ERROR => true
            ],
            $event->getDataForLineItem(20)
        );
    }

    public function testOnLineItemDataWhenHasNoViolationsInKitItemButHasGeneralError(): void
    {
        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $this->validationGroupsProvider
            ->expects(self::any())
            ->method('getAllValidationGroups')
            ->willReturn(['datagrid_line_items_data_for_checkout', 'datagrid_line_items_data_for_rfq']);

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
        $lineItemsHolder = (new ProductLineItemsHolderDTO())->setLineItems(new ArrayCollection($lineItems));
        $constraintViolationList = new ConstraintViolationList();
        $constraintViolationList->add($this->createViolation('warning1', 'warning', $lineItem1, $lineItemsHolder));

        $this->validator->expects(self::once())
            ->method('validate')
            ->with(
                $lineItemsHolder,
                null,
                ['datagrid_line_items_data_for_checkout', 'datagrid_line_items_data_for_rfq']
            )
            ->willReturn($constraintViolationList);

        $this->listener->onLineItemData($event);

        self::assertEquals(
            [
                DatagridLineItemsDataValidationListener::WARNINGS => ['warning1'],
                DatagridLineItemsDataValidationListener::ERRORS => []
            ],
            $event->getDataForLineItem(10)
        );
        self::assertEquals(
            [
                DatagridKitLineItemsDataListener::IS_KIT => true,
                DatagridKitLineItemsDataListener::SUB_DATA => [],
                DatagridLineItemsDataValidationListener::WARNINGS => [
                    'oro.shoppinglist.product_kit_line_item.general_error.message.translated'
                ],
                DatagridLineItemsDataValidationListener::ERRORS => [],
                DatagridLineItemsDataValidationListener::KIT_HAS_GENERAL_ERROR => true
            ],
            $event->getDataForLineItem(20)
        );
    }
}
