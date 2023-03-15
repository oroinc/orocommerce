<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ValidationBundle\Validator\Constraints\DatesChain;
use Oro\Bundle\ValidationBundle\Validator\Constraints\DatesChainValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class DatesChainValidatorTest extends ConstraintValidatorTestCase
{
    private const FIRST_LABEL = 'First';
    private const SECOND_LABEL = 'Second';
    private const THIRD_LABEL = 'Third';

    protected function createValidator()
    {
        return new DatesChainValidator(PropertyAccess::createPropertyAccessor());
    }

    private function createTestObject(?\DateTime $first, ?\DateTime $second, ?\DateTime $third): \stdClass
    {
        $result = new \stdClass();
        $result->first = $first;
        $result->second = $second;
        $result->third = $third;

        return $result;
    }

    private function createConstraint(): DatesChain
    {
        $constraint = new DatesChain();
        $constraint->chain = [
            'first'  => self::FIRST_LABEL,
            'second' => self::SECOND_LABEL,
            'third'  => self::THIRD_LABEL
        ];

        return $constraint;
    }

    public function testValidateForValidChain(): void
    {
        $value = $this->createTestObject(
            new \DateTime('2016-01-01'),
            new \DateTime('2016-01-02'),
            new \DateTime('2016-01-03')
        );

        $constraint = $this->createConstraint();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateForValidChainWithNull(): void
    {
        $value = $this->createTestObject(
            new \DateTime('2016-01-01'),
            null,
            new \DateTime('2016-01-03')
        );

        $constraint = $this->createConstraint();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateForValidChainWithFirstNull(): void
    {
        $value = $this->createTestObject(
            null,
            new \DateTime('2016-01-02'),
            new \DateTime('2016-01-03')
        );

        $constraint = $this->createConstraint();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateForNotValid(): void
    {
        $value = $this->createTestObject(
            new \DateTime('2016-01-03'),
            new \DateTime('2016-01-02'),
            new \DateTime('2016-01-01')
        );

        $constraint = $this->createConstraint();
        $this->validator->validate($value, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setParameters(['later' => self::SECOND_LABEL, 'earlier' => self::FIRST_LABEL])
            ->atPath('property.path.second')
            ->buildNextViolation($constraint->message)
            ->setParameters(['later' => self::THIRD_LABEL, 'earlier' => self::SECOND_LABEL])
            ->atPath('property.path.third')
            ->assertRaised();
    }

    public function testValidateForNotValidWithNull(): void
    {
        $value = $this->createTestObject(
            new \DateTime('2016-01-02'),
            null,
            new \DateTime('2016-01-01')
        );

        $constraint = $this->createConstraint();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameters(['later' => self::THIRD_LABEL, 'earlier' => self::FIRST_LABEL])
            ->atPath('property.path.third')
            ->assertRaised();
    }
}
