<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ShippingBundle\Checker\ShippingRuleEnabledCheckerInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Validator\Constraints\ShippingRuleEnable;
use Oro\Bundle\ShippingBundle\Validator\Constraints\ShippingRuleEnableValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ShippingRuleEnableValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ShippingRuleEnabledCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $ruleEnabledChecker;

    protected function setUp(): void
    {
        $this->ruleEnabledChecker = $this->createMock(ShippingRuleEnabledCheckerInterface::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        return new ShippingRuleEnableValidator($this->ruleEnabledChecker);
    }

    private function getShippingRule(bool $isEnabled): ShippingMethodsConfigsRule
    {
        $shippingRule = new ShippingMethodsConfigsRule();
        $shippingRule->setRule((new Rule())->setEnabled($isEnabled));

        return $shippingRule;
    }

    public function testValidateForWrongObject()
    {
        $this->expectException(UnexpectedTypeException::class);

        $constraint = new ShippingRuleEnable();
        $this->validator->validate(null, $constraint);
    }

    public function testValidateForNotEnabledRule()
    {
        $this->ruleEnabledChecker->expects(self::never())
            ->method('canBeEnabled');

        $constraint = new ShippingRuleEnable();
        $this->validator->validate($this->getShippingRule(false), $constraint);

        $this->assertNoViolation();
    }

    public function testValidateForCanBeEnabledRule()
    {
        $this->ruleEnabledChecker->expects(self::once())
            ->method('canBeEnabled')
            ->willReturn(true);

        $constraint = new ShippingRuleEnable();
        $this->validator->validate($this->getShippingRule(true), $constraint);

        $this->assertNoViolation();
    }

    public function testValidateForCantBeEnabledRule()
    {
        $this->ruleEnabledChecker->expects(self::once())
            ->method('canBeEnabled')
            ->willReturn(false);

        $constraint = new ShippingRuleEnable();
        $this->validator->validate($this->getShippingRule(true), $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.rule')
            ->assertRaised();
    }
}
