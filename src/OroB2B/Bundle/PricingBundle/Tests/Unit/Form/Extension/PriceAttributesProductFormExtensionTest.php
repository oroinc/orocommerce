<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\PricingBundle\Form\Extension\PriceAttributesProductFormExtension;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension\Stub\ProductTypeStub;

class PriceAttributesProductFormExtensionTest extends FormIntegrationTestCase
{
    /**
     * @var PriceAttributesProductFormExtension
     */
    protected $productAttributeFormExtension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->productAttributeFormExtension = new PriceAttributesProductFormExtension();
        parent::setUp();
    }

    public function testGetExtendedType()
    {
        $this->productAttributeFormExtension->getExtendedType();
        $this->assertSame(ProductType::NAME, $this->productAttributeFormExtension->getExtendedType());
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $extensions = [
            new PreloadedExtension(
                [
                    ProductType::NAME => new ProductTypeStub()
                ],
                [
                    ProductType::NAME => [
                        $this->productAttributeFormExtension
                    ]
                ]
            )
        ];

        return $extensions;
    }

    public function testSubmit()
    {
        $form = $this->factory->create(ProductType::NAME, new Product(), []);

        $form->submit([]);
        $this->assertTrue($form->isValid());
    }
}
