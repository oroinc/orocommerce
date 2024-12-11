<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Model\ProductKitItemAwareInterface;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductKitItemLineItemQuantityRange;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductKitItemLineItemQuantityRangeValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProductKitItemLineItemQuantityRangeValidatorTest extends ConstraintValidatorTestCase
{
    #[\Override]
    protected function createValidator(): ProductKitItemLineItemQuantityRangeValidator
    {
        return new ProductKitItemLineItemQuantityRangeValidator();
    }

    public function testUnexpectedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(10, $this->createMock(Constraint::class));
    }

    public function testValidateWithNullValue(): void
    {
        $this->validator->validate(null, new ProductKitItemLineItemQuantityRange());

        $this->assertNoViolation();
    }

    public function testValidateWithUnexpectedObject(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->setObject(new \stdClass());
        $this->validator->validate(10, new ProductKitItemLineItemQuantityRange());
    }

    public function testValidateWhenNoKitItem(): void
    {
        $object = $this->createMock(ProductKitItemAwareInterface::class);
        $object->expects(self::once())
            ->method('getKitItem')
            ->willReturn(null);

        $this->setObject($object);
        $this->validator->validate(10, new ProductKitItemLineItemQuantityRange());

        $this->assertNoViolation();
    }

    public function testValidateWhenValueInRange(): void
    {
        $kitItem = new ProductKitItem();
        $kitItem->setMinimumQuantity(1);
        $kitItem->setMaximumQuantity(100);

        $object = $this->createMock(ProductKitItemAwareInterface::class);
        $object->expects(self::exactly(3))
            ->method('getKitItem')
            ->willReturn($kitItem);

        $this->setObject($object);
        $this->validator->validate(10, new ProductKitItemLineItemQuantityRange());

        $this->assertNoViolation();
    }

    public function testValidateWhenValueOutOfRange(): void
    {
        $kitItem = new ProductKitItem();
        $kitItem->setMinimumQuantity(11);
        $kitItem->setMaximumQuantity(100);

        $object = $this->createMock(ProductKitItemAwareInterface::class);
        $object->expects(self::exactly(3))
            ->method('getKitItem')
            ->willReturn($kitItem);

        $this->setObject($object);
        $this->validator->validate(10, new ProductKitItemLineItemQuantityRange());

        $rangeConstraint = new Range(['min' => 99999]);
        $this
            ->buildViolation($rangeConstraint->notInRangeMessage)
            ->setParameters([
                '{{ min }}' => '11',
                '{{ max }}' => '100',
                '{{ value }}' => '10',
                '{{ max_limit_path }}' => 'kitItem.maximumQuantity',
                '{{ min_limit_path }}' => 'kitItem.minimumQuantity'
            ])
            ->setCode(Range::NOT_IN_RANGE_ERROR)
            ->assertRaised();
    }


    public function testValidateWhenValueOutOfRangeAndCustomValidationMessage(): void
    {
        $kitItem = new ProductKitItem();
        $kitItem->setMinimumQuantity(11);
        $kitItem->setMaximumQuantity(100);

        $object = $this->createMock(ProductKitItemAwareInterface::class);
        $object->expects(self::exactly(3))
            ->method('getKitItem')
            ->willReturn($kitItem);

        $this->setObject($object);
        $constraint = new ProductKitItemLineItemQuantityRange([
            'notInRangeMessage' => 'Some message'
        ]);
        $this->validator->validate(10, $constraint);

        $this
            ->buildViolation($constraint->notInRangeMessage)
            ->setParameters([
                '{{ min }}' => '11',
                '{{ max }}' => '100',
                '{{ value }}' => '10',
                '{{ max_limit_path }}' => 'kitItem.maximumQuantity',
                '{{ min_limit_path }}' => 'kitItem.minimumQuantity'
            ])
            ->setCode(Range::NOT_IN_RANGE_ERROR)
            ->assertRaised();
    }
}
