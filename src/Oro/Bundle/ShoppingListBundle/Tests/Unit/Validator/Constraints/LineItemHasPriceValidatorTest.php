<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\LineItemHasPrice;
use Oro\Bundle\ShoppingListBundle\Validator\Constraints\LineItemHasPriceValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class LineItemHasPriceValidatorTest extends ConstraintValidatorTestCase
{
    private ProductPriceProviderInterface&MockObject $productPriceProvider;
    private ProductPriceScopeCriteriaRequestHandler&MockObject $scopeCriteriaRequestHandler;
    private ProductPriceCriteriaFactoryInterface&MockObject $productPriceCriteriaFactory;

    #[\Override]
    protected function setUp(): void
    {
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $this->scopeCriteriaRequestHandler = $this->createMock(ProductPriceScopeCriteriaRequestHandler::class);
        $this->productPriceCriteriaFactory = $this->createMock(ProductPriceCriteriaFactoryInterface::class);

        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): LineItemHasPriceValidator
    {
        return new LineItemHasPriceValidator(
            $this->productPriceProvider,
            $this->scopeCriteriaRequestHandler,
            $this->productPriceCriteriaFactory
        );
    }

    public function testValidateWhenInvalidConstraint(): void
    {
        $constraint = $this->createMock(Constraint::class);
        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, LineItemHasPrice::class)
        );

        $this->validator->validate(new LineItem(), $constraint);
    }

    public function testValidateWhenInvalidValue(): void
    {
        $constraint = new LineItemHasPrice();
        $value = new \stdClass();
        $this->expectExceptionObject(new UnexpectedValueException($value, ProductLineItemInterface::class));

        $this->validator->validate($value, $constraint);
    }

    public function testValidateWhenValueIsNull(): void
    {
        $constraint = new LineItemHasPrice();
        $this->validator->validate(null, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenProductIsConfigurable(): void
    {
        $constraint = new LineItemHasPrice();
        $product = $this->createMock(Product::class);
        $product->method('isConfigurable')
            ->willReturn(true);

        $lineItem = $this->createMock(LineItem::class);
        $lineItem->method('getProduct')
            ->willReturn($product);

        $this->validator->validate($lineItem, $constraint);

        $this->assertNoViolation();
    }


    public function testValidateCheckoutLineItemWithFixedPriceWhenPriceExists(): void
    {
        $constraint = new LineItemHasPrice();
        $product = $this->createMock(Product::class);
        $product->method('isConfigurable')
            ->willReturn(false);

        $price = $this->createMock(Price::class);
        $price->method('getCurrency')
            ->willReturn('USD');

        $lineItem = $this->createMock(CheckoutLineItem::class);
        $lineItem->method('getProduct')
            ->willReturn($product);
        $lineItem->method('isPriceFixed')
            ->willReturn(true);
        $lineItem->method('getPrice')
            ->willReturn($price);

        $this->validator->validate($lineItem, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateCheckoutLineItemWithFixedPriceWhenPriceIsNull(): void
    {
        $constraint = new LineItemHasPrice();
        $product = $this->createMock(Product::class);
        $product->method('isConfigurable')
            ->willReturn(false);

        $lineItem = $this->createMock(CheckoutLineItem::class);
        $lineItem->method('getProduct')
            ->willReturn($product);
        $lineItem->method('isPriceFixed')
            ->willReturn(true);
        $lineItem->method('getPrice')
            ->willReturn(null);

        $this->validator->validate($lineItem, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testValidateCheckoutLineItemWithFixedPriceWhenPriceHasNoCurrency(): void
    {
        $constraint = new LineItemHasPrice();
        $product = $this->createMock(Product::class);
        $product->method('isConfigurable')
            ->willReturn(false);

        $price = $this->createMock(Price::class);
        $price->method('getCurrency')
            ->willReturn(null);

        $lineItem = $this->createMock(CheckoutLineItem::class);
        $lineItem->method('getProduct')
            ->willReturn($product);
        $lineItem->method('isPriceFixed')
            ->willReturn(true);
        $lineItem->method('getPrice')
            ->willReturn($price);

        $this->validator->validate($lineItem, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testValidateCheckoutLineItemWithNonFixedPriceWhenPriceExists(): void
    {
        $constraint = new LineItemHasPrice();
        $product = $this->createMock(Product::class);
        $product->method('isConfigurable')
            ->willReturn(false);

        $priceCriteria = $this->createMock(ProductPriceCriteria::class);
        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $lineItem = $this->createMock(CheckoutLineItem::class);
        $lineItem->method('getProduct')
            ->willReturn($product);
        $lineItem->method('isPriceFixed')
            ->willReturn(false);

        $this->productPriceCriteriaFactory->expects(self::once())
            ->method('createListFromProductLineItems')
            ->with([$lineItem])
            ->willReturn([$priceCriteria]);

        $this->scopeCriteriaRequestHandler->expects(self::once())
            ->method('getPriceScopeCriteria')
            ->willReturn($scopeCriteria);

        $this->productPriceProvider->expects(self::once())
            ->method('getMatchedPrices')
            ->with([$priceCriteria], $scopeCriteria)
            ->willReturn(['some_price_data']);

        $this->validator->validate($lineItem, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateCheckoutLineItemWithNonFixedPriceWhenNoPriceExists(): void
    {
        $constraint = new LineItemHasPrice();
        $product = $this->createMock(Product::class);
        $product->method('isConfigurable')
            ->willReturn(false);

        $priceCriteria = $this->createMock(ProductPriceCriteria::class);
        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $lineItem = $this->createMock(CheckoutLineItem::class);
        $lineItem->method('getProduct')
            ->willReturn($product);
        $lineItem->method('isPriceFixed')
            ->willReturn(false);

        $this->productPriceCriteriaFactory->expects(self::once())
            ->method('createListFromProductLineItems')
            ->with([$lineItem])
            ->willReturn([$priceCriteria]);

        $this->scopeCriteriaRequestHandler->expects(self::once())
            ->method('getPriceScopeCriteria')
            ->willReturn($scopeCriteria);

        $this->productPriceProvider->expects(self::once())
            ->method('getMatchedPrices')
            ->with([$priceCriteria], $scopeCriteria)
            ->willReturn([]);

        $this->validator->validate($lineItem, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testValidateShoppingListLineItemWhenPriceExists(): void
    {
        $constraint = new LineItemHasPrice();
        $product = $this->createMock(Product::class);
        $product->method('isConfigurable')
            ->willReturn(false);

        $priceCriteria = $this->createMock(ProductPriceCriteria::class);
        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $lineItem = $this->createMock(LineItem::class);
        $lineItem->method('getProduct')
            ->willReturn($product);

        $this->productPriceCriteriaFactory->expects(self::once())
            ->method('createListFromProductLineItems')
            ->with([$lineItem])
            ->willReturn([$priceCriteria]);

        $this->scopeCriteriaRequestHandler->expects(self::once())
            ->method('getPriceScopeCriteria')
            ->willReturn($scopeCriteria);

        $this->productPriceProvider->expects(self::once())
            ->method('getMatchedPrices')
            ->with([$priceCriteria], $scopeCriteria)
            ->willReturn(['some_price_data']);

        $this->validator->validate($lineItem, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateShoppingListLineItemWhenNoPriceExists(): void
    {
        $constraint = new LineItemHasPrice();
        $product = $this->createMock(Product::class);
        $product->method('isConfigurable')
            ->willReturn(false);

        $priceCriteria = $this->createMock(ProductPriceCriteria::class);
        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $lineItem = $this->createMock(LineItem::class);
        $lineItem->method('getProduct')
            ->willReturn($product);

        $this->productPriceCriteriaFactory->expects(self::once())
            ->method('createListFromProductLineItems')
            ->with([$lineItem])
            ->willReturn([$priceCriteria]);

        $this->scopeCriteriaRequestHandler->expects(self::once())
            ->method('getPriceScopeCriteria')
            ->willReturn($scopeCriteria);

        $this->productPriceProvider->expects(self::once())
            ->method('getMatchedPrices')
            ->with([$priceCriteria], $scopeCriteria)
            ->willReturn([]);

        $this->validator->validate($lineItem, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }
}
