<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Validator\Constraints;

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
}
