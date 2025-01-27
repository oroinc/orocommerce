<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Validator\Constraints\HasApplicableShippingRules;
use Oro\Bundle\CheckoutBundle\Validator\Constraints\HasApplicableShippingRulesValidator;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\ShippingMethodActionsInterface;
use Oro\Bundle\SaleBundle\Entity\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class HasApplicableShippingRulesValidatorTest extends ConstraintValidatorTestCase
{
    private ShippingMethodActionsInterface&MockObject $shippingMethodActions;

    #[\Override]
    protected function setUp(): void
    {
        $this->shippingMethodActions = $this->createMock(ShippingMethodActionsInterface::class);

        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): HasApplicableShippingRulesValidator
    {
        return new HasApplicableShippingRulesValidator($this->shippingMethodActions);
    }

    public function testUnexpectedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($this->createMock(Quote::class), $this->createMock(Constraint::class));
    }

    public function testValidateWithNullValue(): void
    {
        $this->validator->validate(null, new HasApplicableShippingRules());

        $this->assertNoViolation();
    }

    public function testValidateWithInvalidType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('invalid_type', new HasApplicableShippingRules());
    }

    public function testValidateWithNoApplicableShippingRulesAndNoErrors(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->shippingMethodActions->expects(self::once())
            ->method('hasApplicableShippingRules')
            ->with(self::identicalTo($checkout), $this->isInstanceOf(ArrayCollection::class))
            ->willReturn(false);

        $constraint = new HasApplicableShippingRules();
        $this->validator->validate($checkout, $constraint);

        $this->buildViolation($constraint->message)
            ->setCode(HasApplicableShippingRules::CODE)
            ->assertRaised();
    }

    public function testValidateWithNoApplicableShippingRulesAndErrors(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->shippingMethodActions->expects(self::once())
            ->method('hasApplicableShippingRules')
            ->with(self::identicalTo($checkout), $this->isInstanceOf(ArrayCollection::class))
            ->willReturnCallback(function ($checkout, $errors) {
                $errors->add(['message' => 'Some error', 'parameters' => ['param1' => 'value1']]);

                return false;
            });

        $constraint = new HasApplicableShippingRules();
        $this->validator->validate($checkout, $constraint);

        $this->buildViolation('Some error')
            ->setParameters(['param1' => 'value1'])
            ->setCode(HasApplicableShippingRules::CODE)
            ->assertRaised();
    }

    public function testValidateWithApplicableShippingRules(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->shippingMethodActions->expects(self::once())
            ->method('hasApplicableShippingRules')
            ->with(self::identicalTo($checkout), $this->isInstanceOf(ArrayCollection::class))
            ->willReturn(true);

        $this->validator->validate($checkout, new HasApplicableShippingRules());

        $this->assertNoViolation();
    }
}
