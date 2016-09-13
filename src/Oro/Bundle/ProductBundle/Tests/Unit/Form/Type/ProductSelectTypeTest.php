<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\TestFrameworkBundle\Entity\Product;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductHolderTypeStub;

class ProductSelectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductSelectType
     */
    protected $type;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->translator
            ->expects(static::any())
            ->method('trans')
            ->will(static::returnCallback(function ($id, array $params) {
                return $id . ':' . $params['{title}'];
            }));

        $this->type = new ProductSelectType($this->translator);

        parent::setUp();
    }

    public function testGetName()
    {
        $this->assertEquals(ProductSelectType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::NAME, $this->type->getParent());
    }

    public function testConfigureOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolver $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                $this->callback(
                    function (array $options) {
                        $this->assertArrayHasKey('data_parameters', $options);
                        $configs = [
                            'placeholder' => 'oro.product.form.choose',
                            'result_template_twig' => 'OroProductBundle:Product:Autocomplete/result.html.twig',
                            'selection_template_twig' => 'OroProductBundle:Product:Autocomplete/selection.html.twig',
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
     * @param array $inputData
     * @param boolean $withParent
     *
     * @dataProvider finishViewProvider
     */
    public function testFinishViewPlaceholderEmpty(array $inputData = [], $withParent = true)
    {
        $form = $this->factory->create($this->type, null);

        if ($withParent) {
            $formParent = $this->factory->create(new ProductHolderTypeStub(), $inputData['productHolder']);
        } else {
            $formParent = null;
        }

        $form->setParent($formParent);

        $view = $form->createView();
        $this->type->finishView($view, $form, $form->getConfig()->getOptions());

        $this->assertArrayNotHasKey('configs', $view->vars);
    }

    /**
     * @return array
     */
    public function finishViewProvider()
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
        $form = $this->factory->create($this->type, null);

        $formParent = $this->factory->create(new ProductHolderTypeStub(), $this->createProductHolder(1, 'sku'));

        $form->setParent($formParent);

        $view = $form->createView();
        $this->type->finishView($view, $form, $form->getConfig()->getOptions());

        $this->assertArrayHasKey('configs', $view->vars);
        $this->assertArrayHasKey('placeholder', $view->vars['configs']);
        $this->assertEquals('oro.product.removed:sku', $view->vars['configs']['placeholder']);
    }

    /**
     * @param int $id
     * @param string $productSku
     * @param Product $product
     * @return \PHPUnit_Framework_MockObject_MockObject|ProductHolderInterface
     */
    protected function createProductHolder($id, $productSku, Product $product = null)
    {
        /* @var $productHolder \PHPUnit_Framework_MockObject_MockObject|ProductHolderInterface */
        $productHolder = $this->getMock('Oro\Bundle\ProductBundle\Model\ProductHolderInterface');
        $productHolder
            ->expects($this->any())
            ->method('getEntityIdentifier')
            ->willReturn($id);
        $productHolder
            ->expects($this->any())
            ->method('getProduct')
            ->willReturn($product);
        $productHolder
            ->expects($this->any())
            ->method('getProductSku')
            ->willReturn($productSku);

        return $productHolder;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $productSelectType = new ProductSelectType($this->translator);
        $entityType = new EntityType(['1'], OroEntitySelectOrCreateInlineType::NAME);

        return [
            new PreloadedExtension(
                [
                    $productSelectType->getName() => $productSelectType,
                    $entityType->getName() => $entityType,
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    /**
     * @dataProvider finishViewDataProvider
     * @param array $dataParameters
     */
    public function testFinishView(array $dataParameters)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $formView = new FormView();
        $this->type->finishView($formView, $form, [
            'data_parameters' => $dataParameters,
        ]);

        $this->assertArrayHasKey('attr', $formView->vars);
        $attr = $formView->vars['attr'];

        if (!empty($dataParameters)) {
            $this->assertArrayHasKey('data-select2_query_additional_params', $attr);
            $this->assertEquals(
                json_encode(['data_parameters' => $dataParameters]),
                $formView->vars['attr']['data-select2_query_additional_params']
            );
        } else {
            $this->assertEmpty($attr);
        }
    }

    /**
     * @return array
     */
    public function finishViewDataProvider()
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
