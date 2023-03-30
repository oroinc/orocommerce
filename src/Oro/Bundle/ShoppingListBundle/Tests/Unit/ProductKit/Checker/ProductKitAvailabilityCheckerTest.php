<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\ProductKit\Checker;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\ProductKit\Checker\ProductKitAvailabilityChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductKitAvailabilityCheckerTest extends TestCase
{
    private ValidatorInterface|MockObject $validator;

    private ProductKitAvailabilityChecker $checker;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->checker = new ProductKitAvailabilityChecker($this->validator);
    }

    public function testIsAvailableForPurchaseWhenAvailable(): void
    {
        $product = new Product();
        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($product, null, ['product_kit_is_available_for_purchase'])
            ->willReturn(new ConstraintViolationList());

        $constraintViolationList = null;
        self::assertTrue($this->checker->isAvailableForPurchase($product, $constraintViolationList));
        self::assertEquals(new ConstraintViolationList(), $constraintViolationList);
    }

    public function testIsAvailableForPurchaseWhenNotAvailable(): void
    {
        $product = new Product();
        $violation = $this->createMock(ConstraintViolationInterface::class);
        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($product, null, ['product_kit_is_available_for_purchase'])
            ->willReturn(new ConstraintViolationList([$violation]));

        $constraintViolationList = null;
        self::assertFalse($this->checker->isAvailableForPurchase($product, $constraintViolationList));
        self::assertEquals(new ConstraintViolationList([$violation]), $constraintViolationList);
    }
}
