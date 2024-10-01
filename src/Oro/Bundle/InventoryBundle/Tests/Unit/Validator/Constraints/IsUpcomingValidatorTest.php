<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\InventoryBundle\Validator\Constraints\IsUpcoming;
use Oro\Bundle\InventoryBundle\Validator\Constraints\IsUpcomingValidator;
use Oro\Bundle\InventoryBundle\Validator\UpcomingLabelCheckoutLineItemValidator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductLineItemStub;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class IsUpcomingValidatorTest extends ConstraintValidatorTestCase
{
    private UpcomingLabelCheckoutLineItemValidator|MockObject $upcomingLabelCheckoutLineItemValidator;

    #[\Override]
    protected function setUp(): void
    {
        $this->upcomingLabelCheckoutLineItemValidator = $this->createMock(
            UpcomingLabelCheckoutLineItemValidator::class
        );

        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): IsUpcomingValidator
    {
        return new IsUpcomingValidator($this->upcomingLabelCheckoutLineItemValidator);
    }

    public function testValidateWhenNull(): void
    {
        $this->validator->validate(null, new IsUpcoming());

        $this->assertNoViolation();
    }

    public function testValidateWhenNoProduct(): void
    {
        $lineItem = new ProductLineItemStub(42);
        $this->validator->validate($lineItem, new IsUpcoming());

        $this->assertNoViolation();
    }

    public function testValidateWhenNotUpcoming(): void
    {
        $product = (new Product())
            ->setSku('sample-sku');
        $lineItem = (new ProductLineItemStub(42))
            ->setProduct($product);

        $this->upcomingLabelCheckoutLineItemValidator
            ->expects(self::once())
            ->method('getMessageIfUpcoming')
            ->with($lineItem)
            ->willReturn(null);

        $constraint = new IsUpcoming();
        $this->validator->validate($lineItem, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenUpcoming(): void
    {
        $product = (new Product())
            ->setSku('sample-sku');
        $lineItem = (new ProductLineItemStub(42))
            ->setProduct($product);

        $this->upcomingLabelCheckoutLineItemValidator
            ->expects(self::once())
            ->method('getMessageIfUpcoming')
            ->with($lineItem)
            ->willReturn('default message');

        $constraint = new IsUpcoming(['message' => 'sample message']);
        $this->validator->validate($lineItem, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setParameter('{{ product_sku }}', $product->getSku())
            ->atPath('property.path.product')
            ->setCause($lineItem)
            ->setCode(IsUpcoming::IS_UPCOMING)
            ->assertRaised();
    }

    public function testValidateWhenUpcomingWithoutExplicitMessage(): void
    {
        $product = (new Product())
            ->setSku('sample-sku');
        $lineItem = (new ProductLineItemStub(42))
            ->setProduct($product);

        $message = 'default message';
        $this->upcomingLabelCheckoutLineItemValidator
            ->expects(self::once())
            ->method('getMessageIfUpcoming')
            ->with($lineItem)
            ->willReturn($message);

        $constraint = new IsUpcoming();
        $this->validator->validate($lineItem, $constraint);

        $this
            ->buildViolation($message)
            ->setParameter('{{ product_sku }}', $product->getSku())
            ->atPath('property.path.product')
            ->setCause($lineItem)
            ->setCode(IsUpcoming::IS_UPCOMING)
            ->assertRaised();
    }
}
