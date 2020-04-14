<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ShippingBundle\Checker\ShippingRuleEnabledCheckerInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Validator\Constraints\ShippingRuleEnable;
use Oro\Bundle\ShippingBundle\Validator\Constraints\ShippingRuleEnableValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ShippingRuleEnableValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ShippingRuleEnabledCheckerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $ruleEnabledChecker;

    /**
     * @var ShippingRuleEnable
     */
    private $constraint;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface
     */
    private $context;

    /**
     * @var ShippingRuleEnableValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->ruleEnabledChecker = $this->createMock(ShippingRuleEnabledCheckerInterface::class);
        $this->constraint = new ShippingRuleEnable();
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new ShippingRuleEnableValidator($this->ruleEnabledChecker);
        $this->validator->initialize($this->context);
    }

    public function testValidateForWrongObject()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(null, $this->constraint);
    }

    public function testValidateForNotEnabledRule()
    {
        $this->context->expects(static::never())->method('buildViolation');
        $this->ruleEnabledChecker->expects(static::never())->method('canBeEnabled');

        $this->validator->validate($this->getShippingRule(false), $this->constraint);
    }

    public function testValidateForCanBeEnabledRule()
    {
        $this->ruleEnabledChecker->expects(static::once())
            ->method('canBeEnabled')
            ->willReturn(true);

        $this->context->expects(static::never())->method('buildViolation');

        $this->validator->validate($this->getShippingRule(true), $this->constraint);
    }

    public function testValidateForCantBeEnabledRule()
    {
        $this->ruleEnabledChecker->expects(static::once())
            ->method('canBeEnabled')
            ->willReturn(false);

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($builder);

        $builder->expects($this->once())
            ->method('atPath')
            ->willReturn($builder);

        $this->validator->validate($this->getShippingRule(true), $this->constraint);
    }

    /**
     * @param bool $isEnabled
     *
     * @return ShippingMethodsConfigsRule
     */
    private function getShippingRule($isEnabled)
    {
        $shippingRule = new ShippingMethodsConfigsRule();
        $shippingRule->setRule((new Rule())->setEnabled($isEnabled));

        return $shippingRule;
    }
}
