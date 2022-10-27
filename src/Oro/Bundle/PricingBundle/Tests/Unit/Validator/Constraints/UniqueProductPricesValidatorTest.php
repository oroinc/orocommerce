<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Validator\Constraints\UniqueProductPrices;
use Oro\Bundle\PricingBundle\Validator\Constraints\UniqueProductPricesValidator;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueProductPricesValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): UniqueProductPricesValidator
    {
        return new UniqueProductPricesValidator();
    }

    public function testValidateWithoutDuplications()
    {
        $value = new ArrayCollection([
            $this->createPriceList(1, 10, 'kg', 'USD'),
            $this->createPriceList(2, 10, 'kg', 'USD'),
            $this->createPriceList(1, 100, 'kg', 'USD'),
            $this->createPriceList(1, 10, 'item', 'USD'),
            $this->createPriceList(1, 10, 'kg'),
            $this->createPriceList(1, 10, 'kg', 'EUR')
        ]);

        $constraint = new UniqueProductPrices();
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateWithDuplications()
    {
        $value = new ArrayCollection([
            $this->createPriceList(1, 10, 'kg', 'USD'),
            $this->createPriceList(1, 10, 'kg', 'USD')
        ]);

        $constraint = new UniqueProductPrices();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testUnexpectedValue()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "array or Traversable and ArrayAccess", "string" given'
        );

        $value = 'string';

        $constraint = new UniqueProductPrices();
        $this->validator->validate($value, $constraint);
    }

    public function testUnexpectedItem()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'argument of type "Oro\Bundle\PricingBundle\Entity\ProductPrice", "stdClass" given'
        );

        $value = new ArrayCollection([ new \stdClass()]);

        $constraint = new UniqueProductPrices();
        $this->validator->validate($value, $constraint);
    }

    private function createPriceList(
        int $priceListId,
        int $quantity,
        string $unitCode,
        string $currency = null
    ): ProductPrice {
        $unit = new ProductUnit();
        $unit->setCode($unitCode);

        $product = new Product();
        ReflectionUtil::setId($product, 42);
        $product->setSku('sku');

        $priceList = new PriceList();
        ReflectionUtil::setId($priceList, $priceListId);
        // Name is not unique for Price List, so it is set same for all price lists in test
        $priceList->setName('price_list');

        $productPrice = new ProductPrice();
        $productPrice
            ->setProduct($product)
            ->setPriceList($priceList)
            ->setQuantity($quantity)
            ->setUnit($unit);

        if ($currency) {
            $price = new Price();
            $price
                ->setValue(100)
                ->setCurrency($currency);

            $productPrice->setPrice($price);
        }

        return $productPrice;
    }
}
