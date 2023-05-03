<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductStepOneType;
use Oro\Bundle\ProductBundle\Form\Type\ProductTypeType;
use Oro\Bundle\ProductBundle\Provider\ProductTypeProvider;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ProductStepOneTypeTest extends FormIntegrationTestCase
{
    public function testIntention(): void
    {
        $form = $this->factory->create(ProductStepOneType::class);

        self::assertEquals('product', $form->getConfig()->getOptions()['csrf_token_id']);
    }

    public function testGetName(): void
    {
        $type = new ProductStepOneType();
        self::assertEquals(ProductStepOneType::NAME, $type->getName());
    }

    public function testBuildView(): void
    {
        $view = new FormView();
        $form = $this->createMock(FormInterface::class);
        $type = new ProductStepOneType();
        $type->buildView($view, $form, []);

        self::assertArrayHasKey('default_input_action', $view->vars);
        self::assertEquals('oro_product_create', $view->vars['default_input_action']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([new ProductTypeType(new ProductTypeProvider(Product::getTypes()))], [])
        ];
    }
}
