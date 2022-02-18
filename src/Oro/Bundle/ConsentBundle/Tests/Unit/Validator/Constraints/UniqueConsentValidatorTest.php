<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfig;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfigConverter;
use Oro\Bundle\ConsentBundle\Validator\Constraints\UniqueConsent;
use Oro\Bundle\ConsentBundle\Validator\Constraints\UniqueConsentValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueConsentValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): UniqueConsentValidator
    {
        return new UniqueConsentValidator();
    }

    public function testGetTargets()
    {
        $constraint = new UniqueConsent();
        self::assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidationOnValid()
    {
        $constraint = new UniqueConsent();
        $this->validator->validate($this->createConfigsData(2), $constraint);

        $this->assertNoViolation();
    }

    public function testValidationOnValidConsentIdIsNull()
    {
        $configsData = [[ConsentConfigConverter::CONSENT_KEY => null]];

        $constraint = new UniqueConsent();
        $this->validator->validate($configsData, $constraint);

        $this->assertNoViolation();
    }

    public function testValidationOnInvalid()
    {
        $value = array_merge($this->createConfigsData(2), $this->createConfigsData(1));

        $constraint = new UniqueConsent();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path[2].consent')
            ->assertRaised();
    }

    /**
     * @return ConsentConfig[]
     */
    private function createConfigsData(int $count): array
    {
        $result = [];
        for ($i = 1; $i <= $count; $i++) {
            $result[] = [
                ConsentConfigConverter::CONSENT_KEY => $i,
                ConsentConfigConverter::SORT_ORDER_KEY => $i * 100,
            ];
        }

        return $result;
    }
}
