<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Form\Type\ProductStepOneType;
use Oro\Bundle\ProductBundle\Form\Type\ProductTypeType;
use Oro\Bundle\ProductBundle\Provider\ProductTypeProvider;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ProductStepOneTypeTest extends FormIntegrationTestCase
{
    public function testIntention()
    {
        $form = $this->factory->create(ProductStepOneType::class);

        $this->assertEquals(
            'product',
            $form->getConfig()->getOptions()['csrf_token_id']
        );
    }

    public function testGetName()
    {
        $type = new ProductStepOneType();
        $this->assertEquals(ProductStepOneType::NAME, $type->getName());
    }

    public function testBuildView()
    {
        $view = new FormView();
        /** @var FormInterface $form */
        $form = $this->createMock(FormInterface::class);
        $type = new ProductStepOneType();
        $type->buildView($view, $form, []);

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
            new PreloadedExtension([new ProductTypeType($productTypeProvider)], [])
        ];
    }
}
