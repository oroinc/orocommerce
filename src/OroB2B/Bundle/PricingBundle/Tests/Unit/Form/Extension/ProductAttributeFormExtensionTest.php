<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\PricingBundle\Form\Extension\ProductAttributeFormExtension;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension\Stub\ProductTypeStub;

class ProductAttributeFormExtensionTest extends FormIntegrationTestCase
{
    /**
     * @var ProductAttributeFormExtension
     */
    protected $productAttributeFormExtension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->productAttributeFormExtension = new ProductAttributeFormExtension();
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
        $form = $this->factory->create(ProductType::NAME, [], []);

        $form->submit([]);
        $this->assertTrue($form->isValid());
    }
}
