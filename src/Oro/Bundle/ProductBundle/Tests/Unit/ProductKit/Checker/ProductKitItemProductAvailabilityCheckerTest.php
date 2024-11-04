<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\ProductKit\Checker;

use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\ProductKit\Checker\ProductKitItemProductAvailabilityChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductKitItemProductAvailabilityCheckerTest extends TestCase
{
    private ValidatorInterface|MockObject $validator;

    private ProductKitItemProductAvailabilityChecker $checker;

    #[\Override]
    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->checker = new ProductKitItemProductAvailabilityChecker(
            $this->validator,
            ['product_kit_item_product_is_available_for_purchase']
        );
    }

    public function testIsAvailableWhenAvailable(): void
    {
        $productKitItemProduct = new ProductKitItemProduct();
        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($productKitItemProduct, null, ['product_kit_item_product_is_available_for_purchase'])
            ->willReturn(new ConstraintViolationList());

        $constraintViolationList = null;
        self::assertTrue($this->checker->isAvailable($productKitItemProduct, $constraintViolationList));
        self::assertEquals(new ConstraintViolationList(), $constraintViolationList);
    }

    public function testIsAvailableWhenNotAvailable(): void
    {
        $productKitItemProduct = new ProductKitItemProduct();
        $violation = $this->createMock(ConstraintViolationInterface::class);
        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($productKitItemProduct, null, ['product_kit_item_product_is_available_for_purchase'])
            ->willReturn(new ConstraintViolationList([$violation]));

        $constraintViolationList = null;
        self::assertFalse($this->checker->isAvailable($productKitItemProduct, $constraintViolationList));
        self::assertEquals(new ConstraintViolationList([$violation]), $constraintViolationList);
    }
}
