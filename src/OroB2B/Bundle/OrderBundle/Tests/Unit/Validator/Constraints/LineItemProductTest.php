<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Stub;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Validator\Constraints\LineItemProduct;
use OroB2B\Bundle\OrderBundle\Validator\Constraints\LineItemProductValidator;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class LineItemProductTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LineItemProduct
     */
    protected $constraint;

    protected function setUp()
    {
        $this->constraint = new LineItemProduct();
    }

    public function testGetTargets()
    {
        $this->assertEquals(LineItemProduct::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Value must be instance of "OroB2B\Bundle\OrderBundle\Entity\OrderLineItem"
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
     * @param OrderLineItem|null $value
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
                ->with('product')
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
                new OrderLineItem(),
                ['orob2b.order.orderlineitem.product.blank', 'orob2b.order.orderlineitem.product_price.blank']
            ],
            [(new OrderLineItem())->setProduct(new Product()), 'orob2b.order.orderlineitem.product_price.blank'],
            [(new OrderLineItem())->setPrice(Price::create(1, 'USD')), 'orob2b.order.orderlineitem.product.blank']
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
