<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\PricingBundle\Form\Type\ProductAttributePriceType;
use OroB2B\Bundle\PricingBundle\Form\Type\ProductAttributePriceCollectionType;
use OroB2B\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ProductAttributePriceCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @return array
     */
    protected function getExtensions()
    {
        $extensions = [
            new PreloadedExtension(
                [
                    ProductAttributePriceCollectionType::NAME => new ProductAttributePriceCollectionType(),
                    ProductAttributePriceType::NAME => new ProductAttributePriceType()
                ],
                []
            )
        ];

        return $extensions;
    }

    public function testSubmit()
    {
        $data = [
            (new PriceAttributeProductPrice())->setPrice(Price::create('100', 'USD')),
            (new PriceAttributeProductPrice())->setPrice(Price::create('80', 'EUR')),
        ];
        $form = $this->factory->create(ProductAttributePriceCollectionType::NAME, $data, []);

        $form->submit([
            [ProductAttributePriceType::PRICE => '200'],
            [ProductAttributePriceType::PRICE => '300'],
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
    }

    public function testFormViewVariablesAdded()
    {
        $attributePriceList = new PriceAttributePriceList();
        $attributePriceList->addCurrencyByCode('USD')
            ->addCurrencyByCode('EUR');

        $product = new Product();
        $product->addUnitPrecision((new ProductUnitPrecision())->setUnit((new ProductUnit())->setCode('item')));

        $price1 = (new PriceAttributeProductPrice())
            ->setPrice(Price::create('100', 'USD'))
            ->setProduct($product)
            ->setPriceList($attributePriceList);

        $price2 = (new PriceAttributeProductPrice())
            ->setPrice(Price::create('80', 'EUR'))
            ->setProduct($product)
            ->setPriceList($attributePriceList);

        $form = $this->factory->create(ProductAttributePriceCollectionType::NAME, [$price1, $price2], []);
        $view = $form->createView();

        $this->assertSame(['EUR', 'USD'], $view->vars['currencies']);
        $this->assertSame(['item'], $view->vars['units']);
    }
}
