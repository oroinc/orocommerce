<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Validator\Constraints\LineItemProduct;
use Oro\Bundle\OrderBundle\Validator\Constraints\LineItemProductValidator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class LineItemProductValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new LineItemProductValidator();
    }

    public function testGetTargets()
    {
        $constraint = new LineItemProduct();
        $this->assertEquals(LineItemProduct::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidateException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value must be instance of "Oro\Bundle\OrderBundle\Entity\OrderLineItem"');

        $constraint = new LineItemProduct();
        $this->validator->validate(null, $constraint);
    }

    public function testValidateNoProduct()
    {
        $value = (new OrderLineItem())->setPrice(Price::create(1, 'USD'));

        $constraint = new LineItemProduct();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->emptyProductMessage)
            ->atPath('property.path.product')
            ->assertRaised();
    }

    public function testValidateNoProductPrice()
    {
        $value = (new OrderLineItem())->setProduct(new Product());

        $constraint = new LineItemProduct();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->priceNotFoundMessage)
            ->atPath('property.path.product')
            ->assertRaised();
    }

    public function testValidateNoProductAndProductPrice()
    {
        $value = new OrderLineItem();

        $constraint = new LineItemProduct();
        $this->validator->validate($value, $constraint);

        $this
            ->buildViolation($constraint->emptyProductMessage)
            ->atPath('property.path.product')
            ->buildNextViolation($constraint->priceNotFoundMessage)
            ->atPath('property.path.product')
            ->assertRaised();
    }
}
