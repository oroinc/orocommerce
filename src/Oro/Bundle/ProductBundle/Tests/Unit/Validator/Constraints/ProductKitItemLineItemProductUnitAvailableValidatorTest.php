<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityBundle\Tools\EntityStateChecker;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\ProductKit\Checker\ProductKitItemProductAvailabilityChecker;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemStub;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductKitItemLineItemProductUnitAvailable;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductKitItemLineItemProductUnitAvailableValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductKitItemLineItemProductUnitAvailableValidatorTest extends ConstraintValidatorTestCase
{
    private EntityStateChecker|MockObject $entityStateChecker;

    private ProductKitItemProductAvailabilityChecker|MockObject $kitItemProductAvailabilityChecker;

    private ProductKitItemLineItemProductUnitAvailable $productKitItemLineItemProductUnitAvailableConstraint;

    protected function setUp(): void
    {
        $this->entityStateChecker = $this->createMock(EntityStateChecker::class);
        $this->kitItemProductAvailabilityChecker = $this->createMock(ProductKitItemProductAvailabilityChecker::class);
        $this->productKitItemLineItemProductUnitAvailableConstraint = new ProductKitItemLineItemProductUnitAvailable(
            ['ifChanged' => ['unit']]
        );

        parent::setUp();
    }

    protected function createValidator(): ProductKitItemLineItemProductUnitAvailableValidator
    {
        return new ProductKitItemLineItemProductUnitAvailableValidator($this->entityStateChecker);
    }

    public function testValidateWhenInvalidConstraint(): void
    {
        $constraint = $this->createMock(Constraint::class);
        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, ProductKitItemLineItemProductUnitAvailable::class)
        );

        $this->validator->validate([], $constraint);
    }

    public function testValidateWhenNullValue(): void
    {
        $this->validator->validate(null, $this->productKitItemLineItemProductUnitAvailableConstraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenInvalidValue(): void
    {
        $value = new \stdClass();
        $this->expectExceptionObject(new UnexpectedValueException($value, ProductUnit::class));

        $this->validator->validate($value, $this->productKitItemLineItemProductUnitAvailableConstraint);
    }

    public function testValidateWhenInvalidObject(): void
    {
        $productUnit = new ProductUnit();
        $object = new \stdClass();
        $this->expectExceptionObject(
            new UnexpectedValueException($object, ProductKitItemLineItemInterface::class)
        );

        $this->setObject($object);
        $this->validator->validate($productUnit, $this->productKitItemLineItemProductUnitAvailableConstraint);
    }

    public function testValidateWhenNoKitItem(): void
    {
        $productUnit = new ProductUnit();
        $kitItemLineItem = new ProductKitItemLineItemStub(42);
        $this->setObject($kitItemLineItem);

        $this->validator->validate($productUnit, $this->productKitItemLineItemProductUnitAvailableConstraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenNotNewNotChanged(): void
    {
        $productUnit = (new ProductUnit())->setCode('each');
        $kitItem = new ProductKitItem();
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setKitItem($kitItem);
        $this->setObject($kitItemLineItem);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($kitItemLineItem)
            ->willReturn(false);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isChangedEntity')
            ->with($kitItemLineItem, ['unit'])
            ->willReturn(false);

        $this->validator->validate($productUnit, $this->productKitItemLineItemProductUnitAvailableConstraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenIsNewAndNotAllowed(): void
    {
        $productUnit = (new ProductUnit())->setCode('each');
        $anotherProductUnit = (new ProductUnit())->setCode('code');
        $kitItem = (new ProductKitItem())
            ->setProductUnit($productUnit);
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setKitItem($kitItem);
        $this->setObject($kitItemLineItem);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($kitItemLineItem)
            ->willReturn(true);

        $this->entityStateChecker
            ->expects(self::never())
            ->method('isChangedEntity');

        $this->validator->validate($anotherProductUnit, $this->productKitItemLineItemProductUnitAvailableConstraint);

        $this
            ->buildViolation($this->productKitItemLineItemProductUnitAvailableConstraint->message)
            ->setParameter('{{ product_unit }}', '"' . $anotherProductUnit->getCode() . '"')
            ->setCode(ProductKitItemLineItemProductUnitAvailable::UNIT_NOT_ALLOWED)
            ->setCause($anotherProductUnit)
            ->assertRaised();
    }

    public function testValidateWhenIsNewAndAllowed(): void
    {
        $productUnit = (new ProductUnit())->setCode('each');
        $kitItem = (new ProductKitItem())
            ->setProductUnit($productUnit);
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setKitItem($kitItem);
        $this->setObject($kitItemLineItem);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($kitItemLineItem)
            ->willReturn(true);

        $this->entityStateChecker
            ->expects(self::never())
            ->method('isChangedEntity');

        $this->validator->validate($productUnit, $this->productKitItemLineItemProductUnitAvailableConstraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenIsChangedAndNotAllowed(): void
    {
        $productUnit = (new ProductUnit())->setCode('each');
        $anotherProductUnit = (new ProductUnit())->setCode('item');

        $kitItem = (new ProductKitItem())
            ->setProductUnit($productUnit);
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setKitItem($kitItem);
        $this->setObject($kitItemLineItem);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($kitItemLineItem)
            ->willReturn(false);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isChangedEntity')
            ->with($kitItemLineItem, ['unit'])
            ->willReturn(true);

        $this->validator->validate($anotherProductUnit, $this->productKitItemLineItemProductUnitAvailableConstraint);

        $this
            ->buildViolation($this->productKitItemLineItemProductUnitAvailableConstraint->message)
            ->setParameter('{{ product_unit }}', '"' . $anotherProductUnit->getCode() . '"')
            ->setCode(ProductKitItemLineItemProductUnitAvailable::UNIT_NOT_ALLOWED)
            ->setCause($anotherProductUnit)
            ->assertRaised();
    }

    public function testValidateWhenIsChangedAndAllowed(): void
    {
        $productUnit = (new ProductUnit())->setCode('each');
        $kitItem = (new ProductKitItem())
            ->setProductUnit($productUnit);
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setKitItem($kitItem);
        $this->setObject($kitItemLineItem);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($kitItemLineItem)
            ->willReturn(false);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isChangedEntity')
            ->with($kitItemLineItem, ['unit'])
            ->willReturn(true);

        $this->validator->validate($productUnit, $this->productKitItemLineItemProductUnitAvailableConstraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenNotIfChangedAndNotAllowed(): void
    {
        $productUnit = (new ProductUnit())->setCode('each');
        $anotherProductUnit = (new ProductUnit())->setCode('item');
        $kitItem = (new ProductKitItem())
            ->setProductUnit($productUnit);
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setKitItem($kitItem);
        $this->setObject($kitItemLineItem);

        $this->entityStateChecker
            ->expects(self::never())
            ->method(self::anything());

        $this->productKitItemLineItemProductUnitAvailableConstraint->ifChanged = [];
        $this->validator->validate($anotherProductUnit, $this->productKitItemLineItemProductUnitAvailableConstraint);

        $this
            ->buildViolation($this->productKitItemLineItemProductUnitAvailableConstraint->message)
            ->setParameter('{{ product_unit }}', '"' . $anotherProductUnit->getCode() . '"')
            ->setCode(ProductKitItemLineItemProductUnitAvailable::UNIT_NOT_ALLOWED)
            ->setCause($anotherProductUnit)
            ->assertRaised();
    }

    public function testValidateWhenNotIfChangedAndAllowed(): void
    {
        $productUnit = new ProductUnit();
        $kitItem = (new ProductKitItem())
            ->setProductUnit($productUnit);
        $kitItemLineItem = (new ProductKitItemLineItemStub(42))
            ->setKitItem($kitItem);
        $this->setObject($kitItemLineItem);

        $this->entityStateChecker
            ->expects(self::never())
            ->method(self::anything());

        $this->productKitItemLineItemProductUnitAvailableConstraint->ifChanged = [];
        $this->validator->validate($productUnit, $this->productKitItemLineItemProductUnitAvailableConstraint);

        $this->assertNoViolation();
    }
}
