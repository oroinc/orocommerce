<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Validator\Constraints\ProductPriceCurrency;
use Oro\Bundle\PricingBundle\Validator\Constraints\ProductPriceCurrencyValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ProductPriceCurrencyValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductPriceCurrency
     */
    protected $constraint;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var ProductPriceCurrencyValidator
     */
    protected $validator;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->constraint = new ProductPriceCurrency();
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new ProductPriceCurrencyValidator();
        $this->validator->initialize($this->context);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        unset($this->constraint, $this->context, $this->validator);
    }

    public function testConfiguration()
    {
        $this->assertEquals('oro_pricing_product_price_currency_validator', $this->constraint->validatedBy());
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testGetDefaultOption()
    {
        $this->assertNull($this->constraint->getDefaultOption());
    }

    public function testValidateWithAllowedPrice()
    {
        $price = new Price();
        $price
            ->setValue('50')
            ->setCurrency('USD');

        $productPrice = $this->getProductPrice();
        $productPrice->setPrice($price);

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($productPrice, $this->constraint);
    }

    public function testValidateWithEmptyCurrency()
    {
        $price = new Price();
        $price
            ->setValue('50')
            ->setCurrency('');

        $productPrice = $this->getProductPrice();
        $productPrice->setPrice($price);

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($productPrice, $this->constraint);
    }

    public function testValidateWithNotAllowedCurrency()
    {
        $invalidCurrency = 'ABC';

        $price = new Price();
        $price
            ->setValue('50')
            ->setCurrency($invalidCurrency);

        $productPrice = $this->getProductPrice();
        $productPrice->setPrice($price);

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($builder);
        $builder->expects($this->once())
            ->method('atPath')
            ->with('price.currency')
            ->willReturnSelf();
        $builder->expects($this->once())
            ->method('setParameters')
            ->with($this->equalTo(['%invalidCurrency%' => $invalidCurrency]))
            ->willReturnSelf();
        $builder->expects($this->once())
            ->method('addViolation');

        $this->validator->validate($productPrice, $this->constraint);
    }

    public function testNotExpectedValueException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'must be instance of "Oro\Bundle\PricingBundle\Entity\BaseProductPrice", "NULL" given'
        );

        $this->validator->validate(null, $this->constraint);
    }

    public function testWithoutPrice()
    {
        $productPrice = new ProductPrice();

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($productPrice, $this->constraint);
    }

    public function testWithoutPriceList()
    {
        $productPrice = new ProductPrice();
        $productPrice->setPrice(new Price());

        $this->context
            ->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($productPrice, $this->constraint);
    }

    /**
     * @return ProductPrice
     */
    public function getProductPrice()
    {
        $priceList = new PriceList();
        $priceList->setCurrencies(['USD', 'EUR']);

        $productPrice = new ProductPrice();
        $productPrice->setPriceList($priceList);

        return $productPrice;
    }
}
