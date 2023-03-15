<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\ProductKit\Checker;

use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ShoppingListBundle\ProductKit\Checker\ProductKitItemAvailabilityChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductKitItemAvailabilityCheckerTest extends TestCase
{
    private ValidatorInterface|MockObject $validator;

    private ProductKitItemAvailabilityChecker $checker;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->checker = new ProductKitItemAvailabilityChecker($this->validator);
    }

    public function testIsAvailableForPurchaseWhenAvailable(): void
    {
        $productKitItem = new ProductKitItem();
        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($productKitItem, null, ['product_kit_item_is_available_for_purchase'])
            ->willReturn(new ConstraintViolationList());

        $constraintViolationList = null;
        self::assertTrue($this->checker->isAvailableForPurchase($productKitItem, $constraintViolationList));
        self::assertEquals(new ConstraintViolationList(), $constraintViolationList);
    }

    public function testIsAvailableForPurchaseWhenNotAvailable(): void
    {
        $productKitItem = new ProductKitItem();
        $violation = $this->createMock(ConstraintViolationInterface::class);
        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($productKitItem, null, ['product_kit_item_is_available_for_purchase'])
            ->willReturn(new ConstraintViolationList([$violation]));

        $constraintViolationList = null;
        self::assertFalse($this->checker->isAvailableForPurchase($productKitItem, $constraintViolationList));
        self::assertEquals(new ConstraintViolationList([$violation]), $constraintViolationList);
    }
}
