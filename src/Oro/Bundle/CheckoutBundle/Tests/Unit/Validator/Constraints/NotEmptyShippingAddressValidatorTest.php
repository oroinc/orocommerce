<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Validator\Constraints\NotEmptyShippingAddress;
use Oro\Bundle\CheckoutBundle\Validator\Constraints\NotEmptyShippingAddressValidator;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NotEmptyShippingAddressValidatorTest extends ConstraintValidatorTestCase
{
    #[\Override]
    protected function createValidator(): NotEmptyShippingAddressValidator
    {
        return new NotEmptyShippingAddressValidator();
    }

    public function testUnexpectedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new OrderAddress(), $this->createMock(Constraint::class));
    }

    public function testValidateWithShipToBillingAddress(): void
    {
        $checkout = new Checkout();
        $checkout->setShipToBillingAddress(true);

        $constraint = new NotEmptyShippingAddress();
        $this->validator->validate(null, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithNullValue(): void
    {
        $checkout = new Checkout();

        $this->setObject($checkout);

        $constraint = new NotEmptyShippingAddress();
        $this->validator->validate(null, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testValidateWithUnexpectedValue(): void
    {
        $checkout = new Checkout();

        $this->setObject($checkout);

        $this->expectException(UnexpectedTypeException::class);

        $constraint = new NotEmptyShippingAddress();
        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testValidateWithValidShippingAddress(): void
    {
        $checkout = new Checkout();

        $this->setObject($checkout);

        $constraint = new NotEmptyShippingAddress();
        $this->validator->validate(new OrderAddress(), $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithInvalidObject(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->setObject(new \stdClass());

        $this->validator->validate(new OrderAddress(), new NotEmptyShippingAddress());
    }
}
