<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\ProductBundle\Provider\ProductTypeProvider;
use Oro\Bundle\ProductBundle\Form\Type\ProductTypeType;
use Oro\Bundle\ProductBundle\Form\Type\ProductStepOneType;

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
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $productTypeProvider = new ProductTypeProvider();

        return [
            new PreloadedExtension(
                [
                    ProductTypeType::NAME => new ProductTypeType($productTypeProvider),
                ],
                []
            )
        ];
    }
}
