<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\Model\ProductLineItem;
use Oro\Bundle\ShoppingListBundle\EventListener\DatagridLineItemsDataValidationListener;
use Oro\Bundle\ShoppingListBundle\EventListener\DatagridLineItemsDataValidationMetadataListener;
use Oro\Bundle\ShoppingListBundle\Mapper\ShoppingListInvalidLineItemsMapper;
use Oro\Bundle\ShoppingListBundle\Provider\InvalidShoppingListLineItemsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;

final class DatagridLineItemsDataValidationMetadataListenerTest extends TestCase
{
    private InvalidShoppingListLineItemsProvider&MockObject $invalidShoppingListLineItemsProvider;
    private DatagridLineItemsDataValidationMetadataListener $listener;

    protected function setUp(): void
    {
        $this->invalidShoppingListLineItemsProvider = $this->createMock(InvalidShoppingListLineItemsProvider::class);
        $this->listener = new DatagridLineItemsDataValidationMetadataListener(
            $this->invalidShoppingListLineItemsProvider
        );
    }

    public function testOnLineItemDataWithEmptyLineItems(): void
    {
        $datagrid = new Datagrid(
            'test-grid',
            DatagridConfiguration::create([]),
            new ParameterBag(['triggered_by' => 'checkout'])
        );
        $event = new DatagridLineItemsDataEvent([], [], $datagrid, []);

        $this->invalidShoppingListLineItemsProvider
            ->expects(self::never())
            ->method('getInvalidItemsViolations');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWithNoViolations(): void
    {
        $lineItem = new ProductLineItem(1);
        $datagrid = new Datagrid(
            'test-grid',
            DatagridConfiguration::create([]),
            new ParameterBag(['triggered_by' => 'checkout'])
        );
        $event = new DatagridLineItemsDataEvent([1 => $lineItem], [], $datagrid, []);

        $violations = [
            ShoppingListInvalidLineItemsMapper::ERRORS => [],
            ShoppingListInvalidLineItemsMapper::WARNINGS => [],
        ];

        $this->invalidShoppingListLineItemsProvider
            ->expects(self::once())
            ->method('getInvalidItemsViolations')
            ->with($event->getLineItems(), 'checkout')
            ->willReturn($violations);

        $this->listener->onLineItemData($event);

        self::assertEquals([], $event->getDataForLineItem(1));
    }

    public function testOnLineItemDataWithQuantityViolation(): void
    {
        $lineItem = new ProductLineItem(1);
        $datagrid = new Datagrid(
            'test-grid',
            DatagridConfiguration::create([]),
            new ParameterBag(['triggered_by' => 'checkout'])
        );
        $event = new DatagridLineItemsDataEvent([1 => $lineItem], [], $datagrid, []);

        $quantityViolation = new ConstraintViolation(
            'Invalid quantity',
            null,
            [],
            null,
            'quantity',
            null
        );

        $violations = [
            ShoppingListInvalidLineItemsMapper::ERRORS => [
                1 => [
                    ShoppingListInvalidLineItemsMapper::MESSAGES => [$quantityViolation],
                ],
            ],
            ShoppingListInvalidLineItemsMapper::WARNINGS => [],
        ];

        $this->invalidShoppingListLineItemsProvider
            ->expects(self::once())
            ->method('getInvalidItemsViolations')
            ->with($event->getLineItems(), 'checkout')
            ->willReturn($violations);

        $this->listener->onLineItemData($event);

        $lineItemData = $event->getDataForLineItem(1);
        self::assertTrue($lineItemData['validationMetadata']['enableQuantityInput']);
    }

    public function testOnLineItemDataWithNestedQuantityViolation(): void
    {
        $lineItem = new ProductLineItem(1);
        $datagrid = new Datagrid(
            'test-grid',
            DatagridConfiguration::create([]),
            new ParameterBag(['triggered_by' => 'checkout'])
        );
        $event = new DatagridLineItemsDataEvent([1 => $lineItem], [], $datagrid, []);

        $nestedQuantityViolation = new ConstraintViolation(
            'Invalid nested quantity',
            null,
            [],
            null,
            'kitItemLineItems[0].quantity',
            null
        );

        $violations = [
            ShoppingListInvalidLineItemsMapper::ERRORS => [],
            ShoppingListInvalidLineItemsMapper::WARNINGS => [
                1 => [
                    ShoppingListInvalidLineItemsMapper::MESSAGES => [$nestedQuantityViolation],
                ],
            ],
        ];

        $this->invalidShoppingListLineItemsProvider
            ->expects(self::once())
            ->method('getInvalidItemsViolations')
            ->with($event->getLineItems(), 'checkout')
            ->willReturn($violations);

        $this->listener->onLineItemData($event);

        $lineItemData = $event->getDataForLineItem(1);
        self::assertTrue($lineItemData['validationMetadata']['enableQuantityInput']);
    }

    public function testOnLineItemDataWithNonQuantityViolation(): void
    {
        $lineItem = new ProductLineItem(1);
        $datagrid = new Datagrid(
            'test-grid',
            DatagridConfiguration::create([]),
            new ParameterBag(['triggered_by' => 'checkout'])
        );
        $event = new DatagridLineItemsDataEvent([1 => $lineItem], [], $datagrid, []);

        $productViolation = new ConstraintViolation(
            'Invalid product',
            null,
            [],
            null,
            'product',
            null
        );

        $violations = [
            ShoppingListInvalidLineItemsMapper::ERRORS => [
                1 => [
                    ShoppingListInvalidLineItemsMapper::MESSAGES => [$productViolation],
                ],
            ],
            ShoppingListInvalidLineItemsMapper::WARNINGS => [],
        ];

        $this->invalidShoppingListLineItemsProvider
            ->expects(self::once())
            ->method('getInvalidItemsViolations')
            ->with($event->getLineItems(), 'checkout')
            ->willReturn($violations);

        $this->listener->onLineItemData($event);

        $lineItemData = $event->getDataForLineItem(1);
        self::assertArrayNotHasKey('validationMetadata', $lineItemData);
    }

    public function testOnLineItemDataWithKitItemHavingGeneralError(): void
    {
        $lineItem = new ProductLineItem(1);
        $datagrid = new Datagrid(
            'test-grid',
            DatagridConfiguration::create([]),
            new ParameterBag(['triggered_by' => 'checkout'])
        );
        $event = new DatagridLineItemsDataEvent([1 => $lineItem], [], $datagrid, []);
        $event->setDataForLineItem(1, [
            'isKit' => true,
            DatagridLineItemsDataValidationListener::KIT_HAS_GENERAL_ERROR => true,
        ]);

        $violations = [
            ShoppingListInvalidLineItemsMapper::ERRORS => [],
            ShoppingListInvalidLineItemsMapper::WARNINGS => [],
        ];

        $this->invalidShoppingListLineItemsProvider
            ->expects(self::once())
            ->method('getInvalidItemsViolations')
            ->with($event->getLineItems(), 'checkout')
            ->willReturn($violations);

        $this->listener->onLineItemData($event);

        $lineItemData = $event->getDataForLineItem(1);
        self::assertTrue($lineItemData['validationMetadata']['enableProductKitConfigure']);
    }

    public function testOnLineItemDataWithKitItemWithoutGeneralError(): void
    {
        $lineItem = new ProductLineItem(1);
        $datagrid = new Datagrid(
            'test-grid',
            DatagridConfiguration::create([]),
            new ParameterBag(['triggered_by' => 'checkout'])
        );
        $event = new DatagridLineItemsDataEvent([1 => $lineItem], [], $datagrid, []);
        $event->setDataForLineItem(1, ['isKit' => true]);

        $violations = [
            ShoppingListInvalidLineItemsMapper::ERRORS => [],
            ShoppingListInvalidLineItemsMapper::WARNINGS => [],
        ];

        $this->invalidShoppingListLineItemsProvider
            ->expects(self::once())
            ->method('getInvalidItemsViolations')
            ->with($event->getLineItems(), 'checkout')
            ->willReturn($violations);

        $this->listener->onLineItemData($event);

        $lineItemData = $event->getDataForLineItem(1);
        self::assertFalse($lineItemData['validationMetadata']['enableProductKitConfigure']);
    }
}
