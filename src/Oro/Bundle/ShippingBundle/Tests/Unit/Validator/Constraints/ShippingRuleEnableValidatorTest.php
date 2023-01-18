<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\ShippingBundle\Checker\ShippingRuleEnabledCheckerInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Validator\Constraints\ShippingRuleEnable;
use Oro\Bundle\ShippingBundle\Validator\Constraints\ShippingRuleEnableValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ShippingRuleEnableValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ShippingRuleEnabledCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $ruleEnabledChecker;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    protected function setUp(): void
    {
        $this->ruleEnabledChecker = $this->createMock(ShippingRuleEnabledCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        parent::setUp();
    }

    protected function createValidator(): ShippingRuleEnableValidator
    {
        return new ShippingRuleEnableValidator($this->ruleEnabledChecker, $this->tokenAccessor);
    }

    private function getShippingRule(bool $isEnabled): ShippingMethodsConfigsRule
    {
        $shippingRule = new ShippingMethodsConfigsRule();
        $shippingRule->setRule((new Rule())->setEnabled($isEnabled));

        return $shippingRule;
    }

    public function testGetTargets()
    {
        $constraint = new ShippingRuleEnable();
        self::assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidateForWrongObject()
    {
        $this->expectException(UnexpectedTypeException::class);

        $constraint = new ShippingRuleEnable();
        $this->validator->validate(null, $constraint);
    }

    public function testValidateForNotEnabledRule()
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn(new Organization());

        $this->ruleEnabledChecker->expects(self::never())
            ->method('canBeEnabled');

        $constraint = new ShippingRuleEnable();
        $this->validator->validate($this->getShippingRule(false), $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenNoOrganizationInSecurityContext()
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn(null);

        $this->ruleEnabledChecker->expects(self::never())
            ->method('canBeEnabled');

        $constraint = new ShippingRuleEnable();
        $this->validator->validate($this->getShippingRule(true), $constraint);

        $this->assertNoViolation();
    }

    public function testValidateForCanBeEnabledRule()
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn(new Organization());

        $this->ruleEnabledChecker->expects(self::once())
            ->method('canBeEnabled')
            ->willReturn(true);

        $constraint = new ShippingRuleEnable();
        $this->validator->validate($this->getShippingRule(true), $constraint);

        $this->assertNoViolation();
    }

    public function testValidateForCantBeEnabledRule()
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn(new Organization());

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
