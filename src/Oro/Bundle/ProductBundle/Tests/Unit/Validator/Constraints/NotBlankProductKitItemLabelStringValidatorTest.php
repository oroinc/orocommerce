<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Oro\Bundle\ProductBundle\Validator\Constraints\NotBlankProductKitItemLabelString;
use Oro\Bundle\ProductBundle\Validator\Constraints\NotBlankProductKitItemLabelStringValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NotBlankProductKitItemLabelStringValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): NotBlankProductKitItemLabelStringValidator
    {
        return new NotBlankProductKitItemLabelStringValidator();
    }

    public function testValidateValidProductKitItemLabelUnexpectedTypeException(): void
    {
        $productKitItemLabel = new \stdClass();

        $constraint = new NotBlank();
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($productKitItemLabel, $constraint);
    }

    public function testValidateValidProductKitItemLabelUnexpectedValueException(): void
    {
        $productKitItemLabel = new \stdClass();

        $constraint = new NotBlankProductKitItemLabelString();
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate($productKitItemLabel, $constraint);
    }

    public function testValidateValidProductKitItemNullLabel(): void
    {
        $productKitItemLabel = new ProductKitItemLabel();
        $productKitItemLabel->setFallback('system');
        $productKitItemLabel->setString(null);

        $constraint = new NotBlankProductKitItemLabelString();
        $this->validator->validate($productKitItemLabel, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateValidProductKitItemLabel(): void
    {
        $productKitItemLabel = new ProductKitItemLabel();
        $productKitItemLabel->setFallback(null);
        $productKitItemLabel->setString('string');

        $constraint = new NotBlankProductKitItemLabelString();
        $this->validator->validate($productKitItemLabel, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateInvalidProductKitItemLabel(): void
    {
        $productKitItemLabel = new ProductKitItemLabel();
        $productKitItemLabel->setFallback(null);
        $productKitItemLabel->setString(null);

        $constraint = new NotBlankProductKitItemLabelString();
        $this->validator->validate($productKitItemLabel, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.string')
            ->assertRaised();
    }
}
