<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductTypeType;
use Oro\Bundle\ProductBundle\Provider\ProductTypeProvider;

class ProductTypeTypeTest extends FormIntegrationTestCase
{
    /** @var ProductTypeType */
    protected $productTypeType;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProductTypeProvider */
    protected $productTypeProvider;

    public function setup()
    {
        parent::setUp();
        $this->productTypeProvider = new ProductTypeProvider();
        $this->productTypeType = new ProductTypeType($this->productTypeProvider);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_product_type', $this->productTypeType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->productTypeType->getParent());
    }

    public function testChoices()
    {
        $form = $this->factory->create($this->productTypeType);
        $availableProductTypes = $this->productTypeProvider->getAvailableProductTypes();
        $choices = [];

        foreach ($availableProductTypes as $key => $value) {
            $choices[] = new ChoiceView($key, $key, $value);
        }

        $this->assertEquals(
            $choices,
            $form->createView()->vars['choices']
        );

        $this->assertEquals(
            Product::TYPE_SIMPLE,
            $form->getConfig()->getOptions()['preferred_choices']
        );
    }
}
