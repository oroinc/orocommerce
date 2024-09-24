<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Validator\Constraints\LineItemProduct;
use Oro\Bundle\OrderBundle\Validator\Constraints\LineItemProductValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class LineItemProductValidatorTest extends ConstraintValidatorTestCase
{
    #[\Override]
    protected function createValidator(): LineItemProductValidator
    {
        return new LineItemProductValidator();
    }

    public function testGetTargets(): void
    {
        $constraint = new LineItemProduct();
        self::assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidateException(): void
    {
        $this->expectExceptionObject(new UnexpectedValueException(null, OrderLineItem::class));

        $constraint = new LineItemProduct();
        $this->validator->validate(null, $constraint);
    }

    public function testValidateNoProduct(): void
    {
        $value = (new OrderLineItem())->setPrice(Price::create(1, 'USD'));

        $constraint = new LineItemProduct();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->emptyProductMessage)
            ->atPath('property.path.product')
            ->assertRaised();
    }
}
