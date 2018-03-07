<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Form\Type\ProductStepOneType;
use Oro\Bundle\ProductBundle\Form\Type\ProductTypeType;
use Oro\Bundle\ProductBundle\Provider\ProductTypeProvider;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

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
            $form->getConfig()->getOptions()['csrf_token_id']
        );
    }

    public function testBuildView()
    {
        $view = new FormView();
        /** @var FormInterface $form */
        $form = $this->createMock(FormInterface::class);
        $this->productStepOneType->buildView($view, $form, []);

        $this->assertArrayHasKey('default_input_action', $view->vars);
        $this->assertEquals('oro_product_create', $view->vars['default_input_action']);
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
