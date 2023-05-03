<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\ProductKit\Checker;

use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ShoppingListBundle\ProductKit\Checker\ProductKitItemProductAvailabilityChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductKitItemProductAvailabilityCheckerTest extends TestCase
{
    private ValidatorInterface|MockObject $validator;

    private ProductKitItemProductAvailabilityChecker $checker;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->checker = new ProductKitItemProductAvailabilityChecker($this->validator);
    }

    public function testIsAvailableForPurchaseWhenAvailable(): void
    {
        $productKitItemProduct = new ProductKitItemProduct();
        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($productKitItemProduct, null, ['product_kit_item_product_is_available_for_purchase'])
            ->willReturn(new ConstraintViolationList());

        $constraintViolationList = null;
        self::assertTrue($this->checker->isAvailableForPurchase($productKitItemProduct, $constraintViolationList));
        self::assertEquals(new ConstraintViolationList(), $constraintViolationList);
    }

    public function testIsAvailableForPurchaseWhenNotAvailable(): void
    {
        $productKitItemProduct = new ProductKitItemProduct();
        $violation = $this->createMock(ConstraintViolationInterface::class);
        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($productKitItemProduct, null, ['product_kit_item_product_is_available_for_purchase'])
            ->willReturn(new ConstraintViolationList([$violation]));

        $constraintViolationList = null;
        self::assertFalse($this->checker->isAvailableForPurchase($productKitItemProduct, $constraintViolationList));
        self::assertEquals(new ConstraintViolationList([$violation]), $constraintViolationList);
    }
}
