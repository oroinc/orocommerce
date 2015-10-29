<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductSelectType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new ProductSelectType();
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
                        $this->assertArrayHasKey('autocomplete_alias', $options);
                        $this->assertArrayHasKey('create_form_route', $options);
                        $this->assertArrayHasKey('configs', $options);
                        $this->assertEquals('orob2b_product_visibility_limited', $options['autocomplete_alias']);
                        $this->assertEquals('orob2b_product_create', $options['create_form_route']);
                        $this->assertEquals(
                            [
                                'placeholder' => 'orob2b.product.form.choose',
                                'result_template_twig' => 'OroB2BProductBundle:Product:Autocomplete/result.html.twig',
                                'selection_template_twig'
                                    => 'OroB2BProductBundle:Product:Autocomplete/selection.html.twig',
                            ],
                            $options['configs']
                        );

                        return true;
                    }
                )
            );

        $this->type->configureOptions($resolver);
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
            'data_parameters' => $dataParameters
        ]);

        $this->assertArrayHasKey('attr', $formView->vars);
        $attr = $formView->vars['attr'];

        if (!empty($dataParameters)) {
            $this->assertArrayHasKey('data-select2_query_additional_params', $attr);
            $this->assertEquals(
                json_encode($dataParameters),
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
                    'visibility_data' => [
                        'scope' => 'test'
                    ]
                ]
            ],
            'without data parameters' => [
                'dataParameters' => []
            ],
        ];
    }
}
