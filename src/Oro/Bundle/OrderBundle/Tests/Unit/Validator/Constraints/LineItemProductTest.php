<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Validator\Constraints\LineItemProduct;
use Oro\Bundle\OrderBundle\Validator\Constraints\LineItemProductValidator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class LineItemProductTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LineItemProduct
     */
    protected $constraint;

    protected function setUp(): void
    {
        $this->constraint = new LineItemProduct();
    }

    public function testGetTargets()
    {
        $this->assertEquals(LineItemProduct::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testValidateException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value must be instance of "Oro\Bundle\OrderBundle\Entity\OrderLineItem"');

        /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
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

        /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $validator = $this->getValidator();
        $validator->initialize($context);

        if (!$expectedViolationMessages) {
            $context->expects($this->never())
                ->method($this->anything());
        } else {
            $violation = $this->createMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
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
                ['oro.order.orderlineitem.product.blank', 'oro.order.orderlineitem.product_price.blank']
            ],
            [(new OrderLineItem())->setProduct(new Product()), 'oro.order.orderlineitem.product_price.blank'],
            [(new OrderLineItem())->setPrice(Price::create(1, 'USD')), 'oro.order.orderlineitem.product.blank']
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
