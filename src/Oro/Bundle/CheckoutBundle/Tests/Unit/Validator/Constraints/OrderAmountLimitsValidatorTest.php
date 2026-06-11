<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CheckoutBundle\Provider\OrderLimitFormattedProviderInterface;
use Oro\Bundle\CheckoutBundle\Provider\OrderLimitProviderInterface;
use Oro\Bundle\CheckoutBundle\Validator\Constraints\OrderAmountLimits;
use Oro\Bundle\CheckoutBundle\Validator\Constraints\OrderAmountLimitsValidator;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class OrderAmountLimitsValidatorTest extends ConstraintValidatorTestCase
{
    private OrderLimitProviderInterface&MockObject $orderLimitProvider;

    private OrderLimitFormattedProviderInterface&MockObject $orderLimitFormattedProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->orderLimitProvider = $this->createMock(OrderLimitProviderInterface::class);
        $this->orderLimitFormattedProvider = $this->createMock(OrderLimitFormattedProviderInterface::class);

        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): OrderAmountLimitsValidator
    {
        return new OrderAmountLimitsValidator($this->orderLimitProvider, $this->orderLimitFormattedProvider);
    }

    public function testValidateWithUnexpectedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new ShoppingList(), $this->createMock(Constraint::class));
    }

    public function testValidateWithNullValue(): void
    {
        $this->validator->validate(null, new OrderAmountLimits());

        $this->assertNoViolation();
    }

    public function testValidateWithUnexpectedValueType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new \stdClass(), new OrderAmountLimits());
    }

    public function testValidateWhenWithinLimits(): void
    {
        $shoppingList = new ShoppingList();
        $this->orderLimitProvider->expects(self::once())
            ->method('isMinimumOrderAmountMet')
            ->with(self::identicalTo($shoppingList))
            ->willReturn(true);
        $this->orderLimitProvider->expects(self::once())
            ->method('isMaximumOrderAmountMet')
            ->with(self::identicalTo($shoppingList))
            ->willReturn(true);
        $this->orderLimitFormattedProvider->expects(self::never())
            ->method(self::anything());

        $this->validator->validate($shoppingList, new OrderAmountLimits());

        $this->assertNoViolation();
    }

    public function testValidateWhenBelowMinimum(): void
    {
        $shoppingList = new ShoppingList();
        $this->orderLimitProvider->expects(self::once())
            ->method('isMinimumOrderAmountMet')
            ->with(self::identicalTo($shoppingList))
            ->willReturn(false);
        $this->orderLimitProvider->expects(self::never())
            ->method('isMaximumOrderAmountMet');
        $this->orderLimitFormattedProvider->expects(self::once())
            ->method('getMinimumOrderAmountFormatted')
            ->willReturn('$50.00');
        $this->orderLimitFormattedProvider->expects(self::once())
            ->method('getMinimumOrderAmountDifferenceFormatted')
            ->with(self::identicalTo($shoppingList))
            ->willReturn('$40.00');

        $constraint = new OrderAmountLimits();
        $this->validator->validate($shoppingList, $constraint);

        $this->buildViolation($constraint->minimumMessage)
            ->setParameter('%amount%', '$50.00')
            ->setParameter('%difference%', '$40.00')
            ->setCode(OrderAmountLimits::MINIMUM_NOT_MET_CODE)
            ->assertRaised();
    }

    public function testValidateWhenAboveMaximum(): void
    {
        $shoppingList = new ShoppingList();
        $this->orderLimitProvider->expects(self::once())
            ->method('isMinimumOrderAmountMet')
            ->with(self::identicalTo($shoppingList))
            ->willReturn(true);
        $this->orderLimitProvider->expects(self::once())
            ->method('isMaximumOrderAmountMet')
            ->with(self::identicalTo($shoppingList))
            ->willReturn(false);
        $this->orderLimitFormattedProvider->expects(self::once())
            ->method('getMaximumOrderAmountFormatted')
            ->willReturn('$100.00');
        $this->orderLimitFormattedProvider->expects(self::once())
            ->method('getMaximumOrderAmountDifferenceFormatted')
            ->with(self::identicalTo($shoppingList))
            ->willReturn('$10.00');

        $constraint = new OrderAmountLimits();
        $this->validator->validate($shoppingList, $constraint);

        $this->buildViolation($constraint->maximumMessage)
            ->setParameter('%amount%', '$100.00')
            ->setParameter('%difference%', '$10.00')
            ->setCode(OrderAmountLimits::MAXIMUM_NOT_MET_CODE)
            ->assertRaised();
    }

    public function testValidateWhenBothMinimumAndMaximumViolatedReportsOnlyMinimum(): void
    {
        $shoppingList = new ShoppingList();
        $this->orderLimitProvider->expects(self::once())
            ->method('isMinimumOrderAmountMet')
            ->with(self::identicalTo($shoppingList))
            ->willReturn(false);
        $this->orderLimitProvider->expects(self::never())
            ->method('isMaximumOrderAmountMet');
        $this->orderLimitFormattedProvider->expects(self::once())
            ->method('getMinimumOrderAmountFormatted')
            ->willReturn('$500.00');
        $this->orderLimitFormattedProvider->expects(self::once())
            ->method('getMinimumOrderAmountDifferenceFormatted')
            ->with(self::identicalTo($shoppingList))
            ->willReturn('$200.00');
        $this->orderLimitFormattedProvider->expects(self::never())
            ->method('getMaximumOrderAmountFormatted');
        $this->orderLimitFormattedProvider->expects(self::never())
            ->method('getMaximumOrderAmountDifferenceFormatted');

        $constraint = new OrderAmountLimits();
        $this->validator->validate($shoppingList, $constraint);

        $this->buildViolation($constraint->minimumMessage)
            ->setParameter('%amount%', '$500.00')
            ->setParameter('%difference%', '$200.00')
            ->setCode(OrderAmountLimits::MINIMUM_NOT_MET_CODE)
            ->assertRaised();
    }
}
