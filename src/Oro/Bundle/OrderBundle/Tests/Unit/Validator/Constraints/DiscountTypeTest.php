<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Validator\Constraints\DiscountType;
use Oro\Bundle\OrderBundle\Validator\Constraints\DiscountTypeValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class DiscountTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DiscountType
     */
    protected $constraint;

    protected function setUp(): void
    {
        $this->constraint = new DiscountType();
    }

    public function testGetTargets()
    {
        $this->assertEquals(
            Constraint::CLASS_CONSTRAINT,
            $this->constraint->getTargets()
        );
    }

    public function testValidateException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value must be instance of "Oro\Bundle\OrderBundle\Entity\OrderDiscount"');

        /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $validator = $this->getValidator();
        $validator->initialize($context);
        $validator->validate(new \stdClass(), $this->constraint);
    }

    /**
     * @dataProvider validateDataProvider
     * @param OrderDiscount|null $value
     * @param null|string $expectedViolationMessage
     */
    public function testValidate($value, $expectedViolationMessage = null)
    {
        /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $validator = $this->getValidator();
        $validator->initialize($context);

        if (null === $expectedViolationMessage) {
            $context->expects($this->never())
                ->method(static::anything());
        } else {
            $violation = $this->createMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
            $violation->expects(static::any())
                ->method('atPath')
                ->with('type')
                ->will($this->returnSelf());

            $violation->expects(static::any())
                ->method('atPath')
                ->with('type')
                ->will($this->returnSelf());

            $violation->expects(static::any())
                ->method('setParameter')
                ->with('%valid_types%', implode(',', [OrderDiscount::TYPE_AMOUNT, OrderDiscount::TYPE_PERCENT]))
                ->will($this->returnSelf());

            $context->expects(static::once())
                ->method('buildViolation')
                ->will($this->returnValue($violation));

            $context->expects(static::any())
                ->method('buildViolation')
                ->with($expectedViolationMessage);
        }

        $validator->validate($value, $this->constraint);
    }

    /**
     * @return array
     */
    public function validateDataProvider()
    {
        return [
            [
                new OrderDiscount(),
                'oro.order.discounts.type.error.label'
            ],
            [
                (new OrderDiscount())->setType(OrderDiscount::TYPE_AMOUNT),
            ],
            [
                (new OrderDiscount())->setType(OrderDiscount::TYPE_PERCENT),
            ],
            [
                (new OrderDiscount())->setType('someType'),
                'oro.order.discounts.type.error.label'
            ]
        ];
    }

    /**
     * @return DiscountTypeValidator
     */
    protected function getValidator()
    {
        $validator = $this->constraint->validatedBy();

        return new $validator();
    }
}
