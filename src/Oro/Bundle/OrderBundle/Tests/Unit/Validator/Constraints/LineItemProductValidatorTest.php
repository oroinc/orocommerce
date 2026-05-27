<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Validator\Constraints\LineItemProduct;
use Oro\Bundle\OrderBundle\Validator\Constraints\LineItemProductValidator;
use Oro\Bundle\ProductBundle\Entity\Product;
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

    public function testValidateNoProductWhenNotFreeForm(): void
    {
        $value = (new OrderLineItem())->setPrice(Price::create(1, 'USD'));

        $constraint = new LineItemProduct();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->emptyProductMessage)
            ->atPath('property.path.product')
            ->assertRaised();
    }

    public function testNoViolationWhenProductIsSet(): void
    {
        $product = $this->createMock(Product::class);
        $value = (new OrderLineItem())
            ->setProduct($product);

        $constraint = new LineItemProduct();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateFreeFormProductEmptyRaisesViolationOnFreeFormProductPath(): void
    {
        $value = (new OrderLineItem())
            ->setIsFreeForm(true)
            ->setFreeFormProduct('');

        $constraint = new LineItemProduct();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->emptyFreeFormProductMessage)
            ->atPath('property.path.freeFormProduct')
            ->assertRaised();
    }

    public function testValidateFreeFormProductNullRaisesViolationOnFreeFormProductPath(): void
    {
        $value = (new OrderLineItem())
            ->setIsFreeForm(true);

        $constraint = new LineItemProduct();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->emptyFreeFormProductMessage)
            ->atPath('property.path.freeFormProduct')
            ->assertRaised();
    }

    public function testNoViolationWhenFreeFormProductIsProvided(): void
    {
        $value = (new OrderLineItem())
            ->setIsFreeForm(true)
            ->setFreeFormProduct('Custom Product Name');

        $constraint = new LineItemProduct();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }
}
