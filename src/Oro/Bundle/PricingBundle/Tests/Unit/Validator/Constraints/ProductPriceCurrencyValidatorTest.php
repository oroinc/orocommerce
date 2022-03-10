<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Validator\Constraints\ProductPriceCurrency;
use Oro\Bundle\PricingBundle\Validator\Constraints\ProductPriceCurrencyValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProductPriceCurrencyValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ProductPriceCurrencyValidator
    {
        return new ProductPriceCurrencyValidator();
    }

    private function getProductPrice(): ProductPrice
    {
        $priceList = new PriceList();
        $priceList->setCurrencies(['USD', 'EUR']);

        $productPrice = new ProductPrice();
        $productPrice->setPriceList($priceList);

        return $productPrice;
    }

    public function testValidateWithAllowedPrice()
    {
        $price = new Price();
        $price
            ->setValue('50')
            ->setCurrency('USD');

        $productPrice = $this->getProductPrice();
        $productPrice->setPrice($price);

        $constraint = new ProductPriceCurrency();
        $this->validator->validate($productPrice, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithEmptyCurrency()
    {
        $price = new Price();
        $price
            ->setValue('50')
            ->setCurrency('');

        $productPrice = $this->getProductPrice();
        $productPrice->setPrice($price);

        $constraint = new ProductPriceCurrency();
        $this->validator->validate($productPrice, $constraint);

        $this->assertNoViolation();
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

        $constraint = new ProductPriceCurrency();
        $this->validator->validate($productPrice, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('%invalidCurrency%', $invalidCurrency)
            ->atPath('property.path.price.currency')
            ->assertRaised();
    }

    public function testNotExpectedValueException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'must be instance of "Oro\Bundle\PricingBundle\Entity\BaseProductPrice", "NULL" given'
        );

        $constraint = new ProductPriceCurrency();
        $this->validator->validate(null, $constraint);
    }

    public function testWithoutPrice()
    {
        $productPrice = new ProductPrice();

        $constraint = new ProductPriceCurrency();
        $this->validator->validate($productPrice, $constraint);

        $this->assertNoViolation();
    }

    public function testWithoutPriceList()
    {
        $productPrice = new ProductPrice();
        $productPrice->setPrice(new Price());

        $constraint = new ProductPriceCurrency();
        $this->validator->validate($productPrice, $constraint);

        $this->assertNoViolation();
    }
}
