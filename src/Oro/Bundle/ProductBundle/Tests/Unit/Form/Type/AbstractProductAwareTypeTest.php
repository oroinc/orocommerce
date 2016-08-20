<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\AbstractProductAwareType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\AbstractProductAwareTypeStub;

class AbstractProductAwareTypeTest extends FormIntegrationTestCase
{
    /** @var AbstractProductAwareTypeStub|AbstractProductAwareType */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new AbstractProductAwareTypeStub();
    }

    /** {@inheritdoc} */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    AbstractProductAwareTypeStub::NAME => new AbstractProductAwareTypeStub(),
                ],
                []
            ),
        ];
    }

    /**
     * @param array $options
     * @param mixed $expectedProduct
     * @dataProvider productOptionsDataProvider
     */
    public function testGetProductFromOptions($expectedProduct, array $options = [])
    {
        $form = $this->factory->createNamed(
            AbstractProductAwareTypeStub::NAME,
            AbstractProductAwareTypeStub::NAME,
            null,
            $options
        );

        $this->assertEquals($expectedProduct, $this->formType->getProduct($form));
    }

    /**
     * @return array
     */
    public function productOptionsDataProvider()
    {
        $product = new Product();
        $productHolder = $this->getMock('Oro\Bundle\ProductBundle\Model\ProductHolderInterface');
        $productHolderWithProduct = $this->getMock('Oro\Bundle\ProductBundle\Model\ProductHolderInterface');
        $productHolderWithProduct->expects($this->once())->method('getProduct')->willReturn($product);

        return [
            'product option without product' => [null, ['product' => null]],
            'product option' => [$product, ['product' => $product]],
            'product holder options' => [null, ['product_holder' => null]],
            'product holder options without product' => [null, ['product_holder' => $productHolder]],
            'product holder options with product' => [$product, ['product_holder' => $productHolderWithProduct]],
            'empty options' => [null, []],
        ];
    }

    /**
     * @param mixed $data
     * @param mixed $expectedProduct
     *
     * @dataProvider parentDataProvider
     */
    public function testGetProductFromParent($data, $expectedProduct)
    {
        $parentForm = $this->factory->createNamed('parentForm', 'form');
        $parentForm->add(AbstractProductAwareTypeStub::NAME, AbstractProductAwareTypeStub::NAME);
        $parentForm->add('product', 'form', ['data' => $data]);

        $child = $parentForm->get(AbstractProductAwareTypeStub::NAME);

        $this->assertEquals($expectedProduct, $this->formType->getProduct($child));
    }

    /**
     * @return array
     */
    public function parentDataProvider()
    {
        $product = new Product();
        $productHolder = $this->getMock('Oro\Bundle\ProductBundle\Model\ProductHolderInterface');
        $productHolderWithProduct = $this->getMock('Oro\Bundle\ProductBundle\Model\ProductHolderInterface');
        $productHolderWithProduct->expects($this->once())->method('getProduct')->willReturn($product);

        return [
            'empty' => [null, null],
            'product' => [$product, $product],
            'product holder without product' => [$productHolder, null],
            'product holder' => [$productHolderWithProduct, $product],
        ];
    }

    public function testGetProductFromParentTree()
    {
        $product = new Product();

        $options = ['compound' => true];
        $root = $this->factory->createNamed('root', 'form', $options);
        $root->add('first', 'form');
        $root->get('first')->add('second', 'form', ['compound' => true]);
        $root->get('first')->get('second')->add(
            AbstractProductAwareTypeStub::NAME,
            AbstractProductAwareTypeStub::NAME,
            ['data' => null]
        );

        $root->add('product', 'form', ['data' => $product]);

        $child = $root->get('first')->get('second')->get(AbstractProductAwareTypeStub::NAME);

        $this->assertEquals($product, $this->formType->getProduct($child));
    }

    /**
     * @param array $options
     * @param Product|null $product
     * @param bool $useParentView
     *
     * @dataProvider getProductFromViewDataProvider
     */
    public function testGetProductFromView(array $options, Product $product = null, $useParentView = false)
    {
        $form = $this->factory->createNamed(
            AbstractProductAwareTypeStub::NAME,
            AbstractProductAwareTypeStub::NAME,
            null,
            $options
        );

        $parentView = null;
        if ($useParentView) {
            $parentView = $this->factory->createNamed(
                AbstractProductAwareTypeStub::NAME,
                AbstractProductAwareTypeStub::NAME
            )->createView();
            $parentView->vars['product'] = $product;
        }

        $view = $form->createView();
        $view->vars['product'] = $form->getConfig()->getOption('product');
        $view->parent = $parentView;

        $this->assertEquals($product, $this->formType->getProductFromView($view));
    }

    /**
     * @return array
     */
    public function getProductFromViewDataProvider()
    {
        $product = new Product();

        return [
            'without product' => [
                'options' => [],
                'product' => null,
                'useParentView' => false,
            ],
            'with product' => [
                'options' => ['product' => $product],
                'product' => $product,
                'useParentView' => false,
            ],
            'with parentView' => [
                'options' => [],
                'product' => $product,
                'useParentView' => true,
            ],
        ];
    }

    /**
     * @param Product|null $formProduct
     * @param Product|null $viewProduct
     * @param Product|null $product
     *
     * @dataProvider getProductFromFormOrViewDataProvider
     */
    public function testGetProductFromFormOrView(
        Product $formProduct = null,
        Product $viewProduct = null,
        Product $product = null
    ) {
        $form = $this->factory->createNamed(
            AbstractProductAwareTypeStub::NAME,
            AbstractProductAwareTypeStub::NAME,
            null,
            ['product' => $formProduct]
        );

        $view = $form->createView();
        $view->vars['product'] = $viewProduct;

        $this->assertEquals($product, $this->formType->getProductFromFormOrView($form, $view));
    }

    /**
     * @return array
     */
    public function getProductFromFormOrViewDataProvider()
    {
        $product = new Product();

        return [
            'without product' => [
                'formProduct' => null,
                'viewProduct' => null,
                'product' => null,
            ],
            'form with product' => [
                'formProduct' => $product,
                'viewProduct' => null,
                'product' => $product,
            ],
            'view with product' => [
                'formProduct' => null,
                'viewProduct' => $product,
                'product' => $product,
            ],
        ];
    }
}
