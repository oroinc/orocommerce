<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Stub;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Validator\Constraints\OrderLineItemCount;
use OroB2B\Bundle\OrderBundle\Validator\Constraints\OrderLineItemCountValidator;

class OrderLineItemCountTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrderLineItemCount
     */
    protected $constraint;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var OrderLineItemCountValidator
     */
    protected $validator;

    protected function setUp()
    {
        $this->constraint = new OrderLineItemCount();
        $this->context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');

        $this->validator = new OrderLineItemCountValidator();
        $this->validator->initialize($this->context);
    }

    protected function tearDown()
    {
        unset($this->constraint, $this->context, $this->validator);
    }

    public function testConfiguration()
    {
        $this->assertEquals('orob2b_order_line_items_count_validator', $this->constraint->validatedBy());
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testGetDefaultOption()
    {
        $this->assertNull($this->constraint->getDefaultOption());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Value must be instance of "OroB2B\Bundle\OrderBundle\Entity\Order", "NULL" given
     */
    public function testValidateException()
    {
        $this->validator->validate(null, $this->constraint);
    }

    /**
     * @dataProvider validateDataProvider
     *
     * @param OrderLineItem|null $value
     * @param string|null $expectedViolationMessage
     */
    public function testValidate($value, $expectedViolationMessage = null)
    {
        if ($expectedViolationMessage) {
            $violation = $this->getMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
            $violation->expects($this->once())
                ->method('atPath')
                ->with('lineItems')
                ->willReturnSelf();

            $this->context->expects($this->once())
                ->method('buildViolation')
                ->with($expectedViolationMessage)
                ->willReturn($violation);
        } else {
            $this->context->expects($this->never())
                ->method($this->anything());
        }

        $this->validator->validate($value, $this->constraint);
    }

    /**
     * @return array
     */
    public function validateDataProvider()
    {
        return [
            [
                new Order(),
                'orob2b.order.orderlineitem.count'
            ],
            [
                (new Order())->addLineItem(new OrderLineItem())
            ]
        ];
    }
}
