<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use Oro\Bundle\PricingBundle\Tests\Unit\SystemConfig\ConfigsGeneratorTrait;
use Oro\Bundle\PricingBundle\Validator\Constraints\UniquePriceList;
use Oro\Bundle\PricingBundle\Validator\Constraints\UniquePriceListValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniquePriceListValidatorTest extends ConstraintValidatorTestCase
{
    use ConfigsGeneratorTrait;

    protected function createValidator(): UniquePriceListValidator
    {
        return new UniquePriceListValidator();
    }

    public function testGetTargets()
    {
        $constraint = new UniquePriceList();
        self::assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidationOnValid()
    {
        $constraint = new UniquePriceList();
        $this->validator->validate($this->createConfigs(2), $constraint);

        $this->assertNoViolation();
    }

    public function testValidationOnInvalid()
    {
        $value = array_merge($this->createConfigs(2), $this->createConfigs(1));

        $constraint = new UniquePriceList();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path[2].priceList')
            ->assertRaised();
    }

    public function testValidationOnInvalidArrayValue()
    {
        $value = array_map(function ($item) {
            /** @var PriceListConfig $item */
            return ['priceList' => $item->getPriceList(), 'sortOrder' => $item->getSortOrder()];
        }, array_merge($this->createConfigs(2), $this->createConfigs(1)));

        $constraint = new UniquePriceList();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path[2][priceList]')
            ->assertRaised();
    }
}
