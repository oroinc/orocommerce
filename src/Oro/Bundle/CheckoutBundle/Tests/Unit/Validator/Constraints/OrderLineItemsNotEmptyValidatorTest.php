<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Validator\Constraints\OrderLineItemsNotEmpty;
use Oro\Bundle\CheckoutBundle\Validator\Constraints\OrderLineItemsNotEmptyValidator;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\OrderLineItemsNotEmptyInterface;
use Oro\Bundle\SaleBundle\Entity\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class OrderLineItemsNotEmptyValidatorTest extends ConstraintValidatorTestCase
{
    private OrderLineItemsNotEmptyInterface&MockObject $orderLineItemsNotEmpty;

    #[\Override]
    protected function setUp(): void
    {
        $this->orderLineItemsNotEmpty = $this->createMock(OrderLineItemsNotEmptyInterface::class);

        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): OrderLineItemsNotEmptyValidator
    {
        return new OrderLineItemsNotEmptyValidator($this->orderLineItemsNotEmpty);
    }

    public function testUnexpectedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($this->createMock(Quote::class), $this->createMock(Constraint::class));
    }

    public function testValidateWithNullValue(): void
    {
        $this->validator->validate(null, new OrderLineItemsNotEmpty());

        $this->assertNoViolation();
    }

    public function testValidateWithInvalidType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('invalid_type', new OrderLineItemsNotEmpty());
    }

    public function testValidateWithEmptyOrderLineItemsAndEmptyRfp(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->orderLineItemsNotEmpty->expects(self::once())
            ->method('execute')
            ->with(self::identicalTo($checkout))
            ->willReturn([
                'orderLineItemsNotEmpty' => [],
                'orderLineItemsNotEmptyForRfp' => []
            ]);

        $constraint = new OrderLineItemsNotEmpty();
        $this->validator->validate($checkout, $constraint);

        $this->buildViolation($constraint->notEmptyForRfpMessage)
            ->setCode(OrderLineItemsNotEmpty::EMPTY_FOR_RFP_CODE)
            ->assertRaised();
    }

    public function testValidateWithEmptyOrderLineItems(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->orderLineItemsNotEmpty->expects(self::once())
            ->method('execute')
            ->with(self::identicalTo($checkout))
            ->willReturn([
                'orderLineItemsNotEmpty' => [],
                'orderLineItemsNotEmptyForRfp' => ['some_rfp_item']
            ]);

        $constraint = new OrderLineItemsNotEmpty();
        $this->validator->validate($checkout, $constraint);

        $this->buildViolation($constraint->notEmptyMessage)
            ->setCode(OrderLineItemsNotEmpty::EMPTY_CODE)
            ->assertRaised();
    }

    public function testValidateWithNonEmptyOrderLineItems(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->orderLineItemsNotEmpty->expects(self::once())
            ->method('execute')
            ->with(self::identicalTo($checkout))
            ->willReturn([
                'orderLineItemsNotEmpty' => ['some_order_item'],
                'orderLineItemsNotEmptyForRfp' => ['some_rfp_item']
            ]);

        $this->validator->validate($checkout, new OrderLineItemsNotEmpty());

        $this->assertNoViolation();
    }

    public function testValidateWithCurrencyMismatch(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->orderLineItemsNotEmpty->expects(self::once())
            ->method('execute')
            ->with(self::identicalTo($checkout))
            ->willReturn([
                'orderLineItemsNotEmpty' => [],
                'orderLineItemsNotEmptyForRfp' => ['some_rfp_item'],
                'orderLineItemsSkippedReasons' => [CheckoutLineItemsManager::REASON_CURRENCY_MISMATCH],
            ]);

        $constraint = new OrderLineItemsNotEmpty();
        $this->validator->validate($checkout, $constraint);

        $this->buildViolation($constraint->notEmptyDifferentCurrencyMessage)
            ->setCode(OrderLineItemsNotEmpty::DIFFERENT_CURRENCY_CODE)
            ->assertRaised();
    }

    public function testValidateWithUnsupportedStatusAndEmptyRfp(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->orderLineItemsNotEmpty->expects(self::once())
            ->method('execute')
            ->with(self::identicalTo($checkout))
            ->willReturn([
                'orderLineItemsNotEmpty' => [],
                'orderLineItemsNotEmptyForRfp' => [],
                'orderLineItemsSkippedReasons' => [CheckoutLineItemsManager::REASON_UNSUPPORTED_STATUS],
            ]);

        $constraint = new OrderLineItemsNotEmpty();
        $this->validator->validate($checkout, $constraint);

        $this->buildViolation($constraint->notEmptyForRfpMessage)
            ->setCode(OrderLineItemsNotEmpty::EMPTY_FOR_RFP_CODE)
            ->assertRaised();
    }

    public function testValidateWithUnsupportedStatusAndRfp(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->orderLineItemsNotEmpty->expects(self::once())
            ->method('execute')
            ->with(self::identicalTo($checkout))
            ->willReturn([
                'orderLineItemsNotEmpty' => [],
                'orderLineItemsNotEmptyForRfp' => ['some_rfp_item'],
                'orderLineItemsSkippedReasons' => [CheckoutLineItemsManager::REASON_UNSUPPORTED_STATUS],
            ]);

        $constraint = new OrderLineItemsNotEmpty();
        $this->validator->validate($checkout, $constraint);

        $this->buildViolation($constraint->notEmptyMessage)
            ->setCode(OrderLineItemsNotEmpty::EMPTY_CODE)
            ->assertRaised();
    }

    public function testValidateWithNoPriceAndEmptyRfp(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->orderLineItemsNotEmpty->expects(self::once())
            ->method('execute')
            ->with(self::identicalTo($checkout))
            ->willReturn([
                'orderLineItemsNotEmpty' => [],
                'orderLineItemsNotEmptyForRfp' => [],
                'orderLineItemsSkippedReasons' => [CheckoutLineItemsManager::REASON_NO_PRICE],
            ]);

        $constraint = new OrderLineItemsNotEmpty();
        $this->validator->validate($checkout, $constraint);

        $this->buildViolation($constraint->notEmptyForRfpMessage)
            ->setCode(OrderLineItemsNotEmpty::EMPTY_FOR_RFP_CODE)
            ->assertRaised();
    }

    public function testValidateWithNoPriceAndRfp(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->orderLineItemsNotEmpty->expects(self::once())
            ->method('execute')
            ->with(self::identicalTo($checkout))
            ->willReturn([
                'orderLineItemsNotEmpty' => [],
                'orderLineItemsNotEmptyForRfp' => ['some_rfp_item'],
                'orderLineItemsSkippedReasons' => [CheckoutLineItemsManager::REASON_NO_PRICE],
            ]);

        $constraint = new OrderLineItemsNotEmpty();
        $this->validator->validate($checkout, $constraint);

        $this->buildViolation($constraint->notEmptyMessage)
            ->setCode(OrderLineItemsNotEmpty::EMPTY_CODE)
            ->assertRaised();
    }

    public function testValidateWithMultipleReasons(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->orderLineItemsNotEmpty->expects(self::once())
            ->method('execute')
            ->with(self::identicalTo($checkout))
            ->willReturn([
                'orderLineItemsNotEmpty' => [],
                'orderLineItemsNotEmptyForRfp' => [],
                'orderLineItemsSkippedReasons' => [
                    CheckoutLineItemsManager::REASON_CURRENCY_MISMATCH,
                    CheckoutLineItemsManager::REASON_UNSUPPORTED_STATUS,
                    CheckoutLineItemsManager::REASON_NO_PRICE,
                ],
            ]);

        $constraint = new OrderLineItemsNotEmpty();
        $this->validator->validate($checkout, $constraint);

        $this->buildViolation($constraint->notEmptyDifferentCurrencyMessage)
            ->setCode(OrderLineItemsNotEmpty::DIFFERENT_CURRENCY_CODE)
            ->buildNextViolation($constraint->notEmptyForRfpMessage)
            ->setCode(OrderLineItemsNotEmpty::EMPTY_FOR_RFP_CODE)
            ->assertRaised();
    }

    public function testValidateWithCurrencyMismatchDoesNotFallBackToRfp(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->orderLineItemsNotEmpty->expects(self::once())
            ->method('execute')
            ->with(self::identicalTo($checkout))
            ->willReturn([
                'orderLineItemsNotEmpty' => [],
                'orderLineItemsNotEmptyForRfp' => [],
                'orderLineItemsSkippedReasons' => [CheckoutLineItemsManager::REASON_CURRENCY_MISMATCH],
            ]);

        $constraint = new OrderLineItemsNotEmpty();
        $this->validator->validate($checkout, $constraint);

        $this->buildViolation($constraint->notEmptyDifferentCurrencyMessage)
            ->setCode(OrderLineItemsNotEmpty::DIFFERENT_CURRENCY_CODE)
            ->assertRaised();
    }
}
