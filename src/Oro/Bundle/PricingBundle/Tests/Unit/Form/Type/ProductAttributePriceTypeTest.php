<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Form\Type\ProductAttributePriceType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class ProductAttributePriceTypeTest extends FormIntegrationTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    ProductAttributePriceType::class => new ProductAttributePriceType()
                ],
                []
            )
        ];
    }

    public function testSubmit()
    {
        $productPrice = new PriceAttributeProductPrice();
        $productPrice->setPrice(Price::create('100', 'USD'));
        $form = $this->factory->create(ProductAttributePriceType::class, $productPrice, []);

        $form->submit([ProductAttributePriceType::PRICE => '500']);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
    }

    public function testSubmittedDataMapping()
    {
        $productPrice = new PriceAttributeProductPrice();
        $productPrice->setPrice(Price::create('100', 'USD'));

        $form = $this->factory->create(ProductAttributePriceType::class, $productPrice, []);
        $this->assertSame('100', $form->get(ProductAttributePriceType::PRICE)->getData());

        $form->submit([ProductAttributePriceType::PRICE => '500']);
        $this->assertEquals(Price::create('500', 'USD'), $productPrice->getPrice());
    }

    public function testSubmitWithCorrectPrecision()
    {
        $productPrice = new PriceAttributeProductPrice();
        $productPrice->setPrice(Price::create('100', 'USD'));
        $form = $this->factory->create(ProductAttributePriceType::class, $productPrice, []);

        $form->submit([ProductAttributePriceType::PRICE => '500.1234']);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals('500.1234', $form->get(ProductAttributePriceType::PRICE)->getData());
    }
}
