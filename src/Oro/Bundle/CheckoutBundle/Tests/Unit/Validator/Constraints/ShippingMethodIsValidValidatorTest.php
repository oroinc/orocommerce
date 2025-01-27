<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Validator\Constraints\ShippingMethodIsValid;
use Oro\Bundle\CheckoutBundle\Validator\Constraints\ShippingMethodIsValidValidator;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodTypeStub;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ShippingMethodIsValidValidatorTest extends ConstraintValidatorTestCase
{
    private ShippingMethodProviderInterface|MockObject $shippingMethodProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);
        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): ShippingMethodIsValidValidator
    {
        return new ShippingMethodIsValidValidator($this->shippingMethodProvider);
    }

    private function getCheckout(?string $shippingMethod, ?string $shippingMethodType): Checkout
    {
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethod);
        $checkout->expects(self::once())
            ->method('getShippingMethodType')
            ->willReturn($shippingMethodType);

        return $checkout;
    }

    private function getShippingMethod(array $types, ?string $activeType): ShippingMethodInterface
    {
        $shippingMethodTypes = [];
        $activeShippingMethodType = null;
        foreach ($types as $type) {
            $shippingMethodType = $this->getShippingMethodType($type);
            $shippingMethodTypes[] = $shippingMethodType;
            if ($type === $activeType) {
                $activeShippingMethodType = $shippingMethodType;
            }
        }

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $shippingMethod->expects(self::any())
            ->method('getTypes')
            ->willReturn($shippingMethodTypes);
        if ($types && null !== $activeType) {
            $shippingMethod->expects(self::once())
                ->method('getType')
                ->with($activeType)
                ->willReturn($activeShippingMethodType);
        } else {
            $shippingMethod->expects(self::never())
                ->method('getType');
        }

        return $shippingMethod;
    }

    private function getShippingMethodType(string $type): ShippingMethodTypeInterface
    {
        $shippingMethodType = new ShippingMethodTypeStub();
        $shippingMethodType->setIdentifier($type);

        return $shippingMethodType;
    }

    public function testValidateWithInvalidConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Checkout(), $this->createMock(Constraint::class));
    }

    public function testValidateWithNullValue(): void
    {
        $this->validator->validate(null, new ShippingMethodIsValid());

        $this->assertNoViolation();
    }

    public function testValidateWithInvalidValueType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('invalid_value', new ShippingMethodIsValid());
    }

    public function testValidateWithNoShippingMethod(): void
    {
        $checkout = $this->getCheckout(null, null);

        $this->shippingMethodProvider->expects(self::never())
            ->method('getShippingMethod');

        $constraint = new ShippingMethodIsValid();
        $this->validator->validate($checkout, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithShippingMethodTypeWithoutShippingMethod(): void
    {
        $checkout = $this->getCheckout(null, 'type1');

        $this->shippingMethodProvider->expects(self::never())
            ->method('getShippingMethod');

        $constraint = new ShippingMethodIsValid();
        $this->validator->validate($checkout, $constraint);

        $this->buildViolation($constraint->shippingMethodTypeMessage)
            ->setCode(ShippingMethodIsValid::CODE)
            ->assertRaised();
    }

    public function testValidateWithNonExistentShippingMethod(): void
    {
        $checkout = $this->getCheckout('non_existent_method', null);

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with('non_existent_method')
            ->willReturn(null);

        $constraint = new ShippingMethodIsValid();
        $this->validator->validate($checkout, $constraint);

        $this->buildViolation($constraint->shippingMethodMessage)
            ->setCode(ShippingMethodIsValid::CODE)
            ->assertRaised();
    }

    public function testValidateWithNoShippingMethodType(): void
    {
        $checkout = $this->getCheckout('valid_method', null);

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with('valid_method')
            ->willReturn($this->getShippingMethod(['valid_type'], null));

        $constraint = new ShippingMethodIsValid();
        $this->validator->validate($checkout, $constraint);

        $this->buildViolation($constraint->shippingMethodTypeMessage)
            ->setCode(ShippingMethodIsValid::CODE)
            ->assertRaised();
    }

    public function testValidateWithNoShippingMethodTypes(): void
    {
        $checkout = $this->getCheckout('valid_method', 'unknown_type');

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with('valid_method')
            ->willReturn($this->getShippingMethod([], 'unknown_type'));

        $constraint = new ShippingMethodIsValid();
        $this->validator->validate($checkout, $constraint);

        $this->buildViolation($constraint->shippingMethodTypeMessage)
            ->setCode(ShippingMethodIsValid::CODE)
            ->assertRaised();
    }

    public function testValidateWithUnknownShippingMethodType(): void
    {
        $checkout = $this->getCheckout('valid_method', 'unknown_type');

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with('valid_method')
            ->willReturn($this->getShippingMethod(['known_type'], 'unknown_type'));

        $constraint = new ShippingMethodIsValid();
        $this->validator->validate($checkout, $constraint);

        $this->buildViolation($constraint->shippingMethodTypeMessage)
            ->setCode(ShippingMethodIsValid::CODE)
            ->assertRaised();
    }

    public function testValidateWithKnownShippingMethodAndType(): void
    {
        $checkout = $this->getCheckout('valid_method', 'known_type');

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with('valid_method')
            ->willReturn($this->getShippingMethod(['known_type'], 'known_type'));

        $constraint = new ShippingMethodIsValid();
        $this->validator->validate($checkout, $constraint);

        $this->assertNoViolation();
    }
}
