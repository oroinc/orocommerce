<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductStepOneType;

class ProductStepOneTypeTest extends FormIntegrationTestCase
{
    /** @var  ProductStepOneType $productStatusType */
    protected $productStepOneType;

    public function setup()
    {
        parent::setUp();

        $this->productStepOneType = new ProductStepOneType();
    }

    public function testGetName()
    {
        $this->assertEquals(ProductStepOneType::NAME, $this->productStepOneType->getName());
    }

    public function testIntention()
    {
        $form = $this->factory->create($this->productStepOneType);

        $this->assertEquals(
            'product',
            $form->getConfig()->getOptions()['intention']
        );

        $this->assertEquals(
            'This form should not contain extra fields: "{{ extra_fields }}"',
            $form->getConfig()->getOptions()['extra_fields_message']
        );
    }
}
