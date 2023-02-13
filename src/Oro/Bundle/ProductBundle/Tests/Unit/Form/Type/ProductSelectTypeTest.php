<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductHolderTypeStub;
use Oro\Bundle\TestFrameworkBundle\Entity\Product;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductSelectTypeTest extends FormIntegrationTestCase
{
    private ProductSelectType $type;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(static::any())
            ->method('trans')
            ->willReturnCallback(function ($id, array $params) {
                return $id . ':' . $params['{title}'];
            });

        $this->type = new ProductSelectType($translator);

        parent::setUp();
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::class, $this->type->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                $this->callback(
                    function (array $options) {
                        $this->assertArrayHasKey('data_parameters', $options);
                        $configs = [
                            'placeholder' => 'oro.product.form.choose',
                            'result_template_twig' => '@OroProduct/Product/Autocomplete/result.html.twig',
                            'selection_template_twig' => '@OroProduct/Product/Autocomplete/selection.html.twig',
                        ];
                        $this->assertArrayHasKey('autocomplete_alias', $options);
                        $this->assertArrayHasKey('create_form_route', $options);
                        $this->assertArrayHasKey('configs', $options);
                        $this->assertEquals('oro_product_visibility_limited', $options['autocomplete_alias']);
                        $this->assertEquals('oro_product_create', $options['create_form_route']);
                        $this->assertEquals($configs, $options['configs']);

                        return true;
                    }
                )
            );

        $this->type->configureOptions($resolver);
    }

    /**
     * @dataProvider finishViewProvider
     */
    public function testFinishViewPlaceholderEmpty(array $inputData, bool $withParent)
    {
        $form = $this->factory->create(ProductSelectType::class);

        if ($withParent) {
            $formParent = $this->factory->create(ProductHolderTypeStub::class, $inputData['productHolder']);
        } else {
            $formParent = null;
        }

        $form->setParent($formParent);

        $view = $form->createView();
        $this->type->finishView($view, $form, $form->getConfig()->getOptions());

        $this->assertArrayNotHasKey('configs', $view->vars);
    }

    public function finishViewProvider(): array
    {
        return [
            'without parent form' => [
                'inputData' => [],
                'withParent' => false,
            ],
            'with parent form with null productHolder' => [
                'inputData' => [
                    'productHolder' => null,
                ],
                'withParent' => true,
            ],
            'with parent form with null productHolder id' => [
                'inputData' => [
                    'productHolder' => $this->createProductHolder(0, 'test'),
                ],
                'withParent' => true,
            ],
            'with parent form with productHolder with product' => [
                'inputData' => [
                    'productHolder' => $this->createProductHolder(1, 'sku', new Product()),
                ],
                'withParent' => true,
            ],
        ];
    }

    public function testFinishViewPlaceholder()
    {
        $form = $this->factory->create(ProductSelectType::class);

        $formParent = $this->factory->create(ProductHolderTypeStub::class, $this->createProductHolder(1, 'sku'));

        $form->setParent($formParent);

        $view = $form->createView();
        $this->type->finishView($view, $form, $form->getConfig()->getOptions());

        $this->assertArrayHasKey('configs', $view->vars);
        $this->assertArrayHasKey('placeholder', $view->vars['configs']);
        $this->assertEquals('oro.product.removed:sku', $view->vars['configs']['placeholder']);
    }

    private function createProductHolder(
        int $id,
        string $productSku,
        Product $product = null
    ): ProductHolderInterface {
        $productHolder = $this->createMock(ProductHolderInterface::class);
        $productHolder->expects($this->any())
            ->method('getEntityIdentifier')
            ->willReturn($id);
        $productHolder->expects($this->any())
            ->method('getProduct')
            ->willReturn($product);
        $productHolder->expects($this->any())
            ->method('getProductSku')
            ->willReturn($productSku);

        return $productHolder;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->type,
                    OroEntitySelectOrCreateInlineType::class => new EntityTypeStub(['1'])
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    /**
     * @dataProvider finishViewDataProvider
     */
    public function testFinishView(array $dataParameters)
    {
        $form = $this->createMock(FormInterface::class);

        $formView = new FormView();
        $this->type->finishView($formView, $form, [
            'data_parameters' => $dataParameters,
        ]);

        $this->assertArrayHasKey('attr', $formView->vars);
        $attr = $formView->vars['attr'];

        if (!empty($dataParameters)) {
            $this->assertArrayHasKey('data-select2_query_additional_params', $attr);
            $this->assertEquals(
                json_encode(['data_parameters' => $dataParameters], JSON_THROW_ON_ERROR),
                $formView->vars['attr']['data-select2_query_additional_params']
            );
        } else {
            $this->assertEmpty($attr);
        }
    }

    public function finishViewDataProvider(): array
    {
        return [
            'with data parameters' => [
                'dataParameters' => [
                    'scope' => 'test',
                ],
            ],
            'without data parameters' => [
                'dataParameters' => [],
            ],
        ];
    }
}
