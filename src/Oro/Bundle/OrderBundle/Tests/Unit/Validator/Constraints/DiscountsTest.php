<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Validator\Constraints\Discounts;
use Oro\Bundle\OrderBundle\Validator\Constraints\LineItemProductValidator;

class DiscountsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Discounts
     */
    protected $constraint;

    protected function setUp()
    {
        $this->constraint = new Discounts();
    }

    public function testGetTargets()
    {
        $this->assertEquals(Discounts::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Value must be instance of "Oro\Bundle\OrderBundle\Entity\Order"
     */
    public function testValidateException()
    {
        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $validator = $this->getValidator();
        $validator->initialize($context);
        $validator->validate(null, $this->constraint);
    }

    /**
     * @dataProvider validateDataProvider
     * @param Order|null $value
     * @param array|null|string $expectedViolationMessages
     */
    public function testValidate($value, $expectedViolationMessages = null)
    {
        if ($expectedViolationMessages && !is_array($expectedViolationMessages)) {
            $expectedViolationMessages = [$expectedViolationMessages];
        }

        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $validator = $this->getValidator();
        $validator->initialize($context);

        if (!$expectedViolationMessages) {
            $context->expects($this->never())
                ->method($this->anything());
        } else {
            $violation = $this->getMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
            $violation->expects($this->any())
                ->method('atPath')
                ->with('totalDiscountsAmount')
                ->will($this->returnSelf());

            $context->expects($this->exactly(count($expectedViolationMessages)))
                ->method('buildViolation')
                ->will($this->returnValue($violation));
            for ($i = 0; $i < count($expectedViolationMessages); $i++) {
                $context->expects($this->at($i))
                    ->method('buildViolation')
                    ->with($expectedViolationMessages[$i]);
            }
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
                new Order(),
                []
            ],
            [
                (new Order())->setSubtotal(10)->setTotalDiscounts(Price::create(1, 'USD')),
                []
            ],
            [
                (new Order())->setSubtotal(10)->setTotalDiscounts(Price::create(15, 'USD')),
                ['oro.order.discounts.sum.error.label']
            ]
        ];
    }

    /**
     * @return LineItemProductValidator
     */
    protected function getValidator()
    {
        $validator = $this->constraint->validatedBy();

        return new $validator();
    }
}
