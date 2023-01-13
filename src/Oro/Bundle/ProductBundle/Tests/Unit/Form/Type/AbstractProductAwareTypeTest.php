<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\AbstractProductAwareTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class AbstractProductAwareTypeTest extends FormIntegrationTestCase
{
    /** @var AbstractProductAwareTypeStub */
    private $formType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formType = new AbstractProductAwareTypeStub();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    AbstractProductAwareTypeStub::class => new AbstractProductAwareTypeStub(),
                ],
                []
            ),
        ];
    }

    /**
     * @dataProvider productOptionsDataProvider
     */
    public function testGetProductFromOptions(?Product $expectedProduct, array $options = [])
    {
        $form = $this->factory->createNamed(
            AbstractProductAwareTypeStub::NAME,
            AbstractProductAwareTypeStub::class,
            null,
            $options
        );

        $this->assertEquals($expectedProduct, $this->formType->getProduct($form));
    }

    public function productOptionsDataProvider(): array
    {
        $product = new Product();
        $productHolder = $this->createMock(ProductHolderInterface::class);
        $productHolderWithProduct = $this->createMock(ProductHolderInterface::class);
        $productHolderWithProduct->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

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
     * @dataProvider parentDataProvider
     */
    public function testGetProductFromParent(mixed $data, mixed $expectedProduct)
    {
        $parentForm = $this->factory->createNamed('parentForm');
        $parentForm->add(AbstractProductAwareTypeStub::NAME, AbstractProductAwareTypeStub::class);
        $parentForm->add('product', FormType::class, ['data' => $data]);

        $child = $parentForm->get(AbstractProductAwareTypeStub::NAME);

        $this->assertEquals($expectedProduct, $this->formType->getProduct($child));
    }

    public function parentDataProvider(): array
    {
        $product = new Product();
        $productHolder = $this->createMock(ProductHolderInterface::class);
        $productHolderWithProduct = $this->createMock(ProductHolderInterface::class);
        $productHolderWithProduct->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

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
        $root = $this->factory->createNamed('root', FormType::class, $options);
        $root->add('first', FormType::class);
        $root->get('first')->add('second', FormType::class, ['compound' => true]);
        $root->get('first')->get('second')->add(
            AbstractProductAwareTypeStub::NAME,
            AbstractProductAwareTypeStub::class,
            ['data' => null]
        );

        $root->add('product', FormType::class, ['data' => $product]);

        $child = $root->get('first')->get('second')->get(AbstractProductAwareTypeStub::NAME);

        $this->assertEquals($product, $this->formType->getProduct($child));
    }

    /**
     * @dataProvider getProductFromViewDataProvider
     */
    public function testGetProductFromView(array $options, Product $product = null, bool $useParentView = false)
    {
        $form = $this->factory->createNamed(
            AbstractProductAwareTypeStub::NAME,
            AbstractProductAwareTypeStub::class,
            null,
            $options
        );

        $parentView = null;
        if ($useParentView) {
            $parentView = $this->factory->createNamed(
                AbstractProductAwareTypeStub::NAME,
                AbstractProductAwareTypeStub::class
            )->createView();
            $parentView->vars['product'] = $product;
        }

        $view = $form->createView();
        $view->vars['product'] = $form->getConfig()->getOption('product');
        $view->parent = $parentView;

        $this->assertEquals($product, $this->formType->getProductFromView($view));
    }

    public function getProductFromViewDataProvider(): array
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
     * @dataProvider getProductFromFormOrViewDataProvider
     */
    public function testGetProductFromFormOrView(
        Product $formProduct = null,
        Product $viewProduct = null,
        Product $product = null
    ) {
        $form = $this->factory->createNamed(
            AbstractProductAwareTypeStub::NAME,
            AbstractProductAwareTypeStub::class,
            null,
            ['product' => $formProduct]
        );

        $view = $form->createView();
        $view->vars['product'] = $viewProduct;

        $this->assertEquals($product, $this->formType->getProductFromFormOrView($form, $view));
    }

    public function getProductFromFormOrViewDataProvider(): array
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
