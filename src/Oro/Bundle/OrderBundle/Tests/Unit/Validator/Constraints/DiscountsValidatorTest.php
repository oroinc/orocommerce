<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Unit\Validator\Stub\ConstraintViolationStub;
use Oro\Bundle\OrderBundle\Validator\Constraints\DiscountsValidator;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilder;

class DiscountsValidatorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ExecutionContext|\PHPUnit_Framework_MockObject_MockObject
     */
    private $context;

    /**
     * @var DiscountsValidator
     */
    private $discountsValidator;

    protected function setUp()
    {
        $this->context = $this->createMock(ExecutionContext::class);

        $this->discountsValidator = new DiscountsValidator();
        $this->discountsValidator->initialize($this->context);
    }

    public function testValidateWithNull()
    {
        /** @var Constraint|\PHPUnit_Framework_MockObject_MockObject $constraint **/
        $constraint = $this->createMock(Constraint::class);

        $this->context
            ->expects($this->never())
            ->method('getViolations');

        $this->discountsValidator->validate(null, $constraint);
    }

    public function testValidateWithNotOrderValue()
    {
        /** @var Constraint|\PHPUnit_Framework_MockObject_MockObject $constraint **/
        $constraint = $this->createMock(Constraint::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Value must be instance of "Oro\Bundle\OrderBundle\Entity\Order", "stdClass" given'
        );

        $this->discountsValidator->validate(new \stdClass(), $constraint);
    }

    /**
     * @dataProvider orderDataProvider
     * @param Order $order
     */
    public function testValidate(Order $order)
    {
        /** @var Constraint|\PHPUnit_Framework_MockObject_MockObject $constraint **/
        $constraint = $this->getMockBuilder(Constraint::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context
            ->expects($this->never())
            ->method('getViolations');

        $this->discountsValidator->validate($order, $constraint);
    }

    /**
     * @return array
     */
    public function orderDataProvider()
    {
        return [
            'no total discounts set' => [
                'order' => (new Order())->setSubtotal(100)
            ],
            'no subtotal set' => [
                'order' => (new Order())->setTotalDiscounts(Price::create(101, 'USD'))
            ],
            'subtotal is equal to total discounts' => [
                'order' => (new Order())->setSubtotal(101)->setTotalDiscounts(Price::create(101, 'USD'))
            ],
            'subtotal is greater than total discounts' => [
                'order' => (new Order())->setSubtotal(102)->setTotalDiscounts(Price::create(101, 'USD'))
            ],
        ];
    }

    public function testValidateFailsWhenNoSuchErrorsPreviously()
    {
        $value = (new Order())
            ->setSubtotal(100)
            ->setTotalDiscounts(Price::create(101, 'USD'));

        $this->setContextValue($value);

        /** @var Constraint|\PHPUnit_Framework_MockObject_MockObject $constraint **/
        $constraint = $this->createMock(Constraint::class);

        $violationsList = new ConstraintViolationList();
        $this->context
            ->expects($this->once())
            ->method('getViolations')
            ->willReturn($violationsList);

        $violationBuilder = $this->createMock(ConstraintViolationBuilder::class);
        $violationBuilder
            ->expects($this->once())
            ->method('atPath')
            ->with('totalDiscountsAmount')
            ->willReturnSelf();

        $violationBuilder
            ->expects($this->once())
            ->method('addViolation');

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->willReturn($violationBuilder);

        $this->discountsValidator->validate($value, $constraint);
    }

    public function testValidateFailsWhenWasErrorPreviously()
    {
        $value = (new Order())
            ->setSubtotal(100)
            ->setTotalDiscounts(Price::create(101, 'USD'));

        $this->setContextValue($value);

        /** @var Constraint|\PHPUnit_Framework_MockObject_MockObject $constraint **/
        $constraint = $this->createMock(Constraint::class);

        $violationsList = new ConstraintViolationList([new ConstraintViolationStub($constraint, $value)]);
        $this->context
            ->expects($this->once())
            ->method('getViolations')
            ->willReturn($violationsList);

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->discountsValidator->validate($value, $constraint);
    }

    /**
     * @param mixed $value
     */
    private function setContextValue($value)
    {
        $this->context
            ->expects($this->any())
            ->method('getValue')
            ->willReturn($value);
    }
}
