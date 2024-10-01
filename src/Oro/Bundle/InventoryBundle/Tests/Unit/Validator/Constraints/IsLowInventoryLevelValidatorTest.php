<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\InventoryBundle\Validator\Constraints\IsLowInventoryLevel;
use Oro\Bundle\InventoryBundle\Validator\Constraints\IsLowInventoryLevelValidator;
use Oro\Bundle\InventoryBundle\Validator\LowInventoryCheckoutLineItemValidator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductLineItemStub;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class IsLowInventoryLevelValidatorTest extends ConstraintValidatorTestCase
{
    private LowInventoryCheckoutLineItemValidator|MockObject $lowInventoryCheckoutLineItemValidator;

    #[\Override]
    protected function setUp(): void
    {
        $this->lowInventoryCheckoutLineItemValidator = $this->createMock(LowInventoryCheckoutLineItemValidator::class);

        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): IsLowInventoryLevelValidator
    {
        return new IsLowInventoryLevelValidator($this->lowInventoryCheckoutLineItemValidator);
    }

    public function testValidateWhenNull(): void
    {
        $this->validator->validate(null, new IsLowInventoryLevel());

        $this->assertNoViolation();
    }

    public function testValidateWhenNoProduct(): void
    {
        $lineItem = new ProductLineItemStub(42);
        $this->validator->validate($lineItem, new IsLowInventoryLevel());

        $this->assertNoViolation();
    }

    public function testValidateWhenNotRunningLow(): void
    {
        $product = (new Product())
            ->setSku('sample-sku');
        $lineItem = (new ProductLineItemStub(42))
            ->setProduct($product);

        $this->lowInventoryCheckoutLineItemValidator
            ->expects(self::once())
            ->method('isRunningLow')
            ->with($lineItem)
            ->willReturn(false);

        $constraint = new IsLowInventoryLevel(['message' => 'sample message']);
        $this->validator->validate($lineItem, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenNotRunningLowWithoutExplicitMessage(): void
    {
        $product = (new Product())
            ->setSku('sample-sku');
        $lineItem = (new ProductLineItemStub(42))
            ->setProduct($product);

        $this->lowInventoryCheckoutLineItemValidator
            ->expects(self::once())
            ->method('getMessageIfRunningLow')
            ->with($lineItem)
            ->willReturn(null);

        $constraint = new IsLowInventoryLevel();
        $this->validator->validate($lineItem, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenRunningLow(): void
    {
        $product = (new Product())
            ->setSku('sample-sku');
        $lineItem = (new ProductLineItemStub(42))
            ->setProduct($product);

        $this->lowInventoryCheckoutLineItemValidator
            ->expects(self::once())
            ->method('isRunningLow')
            ->with($lineItem)
            ->willReturn(true);

        $constraint = new IsLowInventoryLevel(['message' => 'sample message']);
        $this->validator->validate($lineItem, $constraint);

        $this
            ->buildViolation($constraint->message)
            ->setParameter('{{ product_sku }}', $product->getSku())
            ->atPath('property.path.quantity')
            ->setCause($lineItem)
            ->setCode(IsLowInventoryLevel::LOW_INVENTORY_LEVEL)
            ->assertRaised();
    }

    public function testValidateWhenRunningLowWithoutExplicitMessage(): void
    {
        $product = (new Product())
            ->setSku('sample-sku');
        $lineItem = (new ProductLineItemStub(42))
            ->setProduct($product);

        $message = 'default message';
        $this->lowInventoryCheckoutLineItemValidator
            ->expects(self::once())
            ->method('getMessageIfRunningLow')
            ->with($lineItem)
            ->willReturn($message);

        $constraint = new IsLowInventoryLevel();
        $this->validator->validate($lineItem, $constraint);

        $this
            ->buildViolation($message)
            ->setParameter('{{ product_sku }}', $product->getSku())
            ->atPath('property.path.quantity')
            ->setCause($lineItem)
            ->setCode(IsLowInventoryLevel::LOW_INVENTORY_LEVEL)
            ->assertRaised();
    }
}
