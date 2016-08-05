<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroAutocompleteType;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductAutocompleteType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;

class ProductAutocompleteTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductAutocompleteType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new ProductAutocompleteType();
    }

    public function testGetName()
    {
        $this->assertEquals(ProductAutocompleteType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(OroAutocompleteType::NAME, $this->type->getParent());
    }

    public function testConfigureOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolver $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->at(0))
            ->method('setDefaults')
            ->with(
                $this->callback(
                    function (array $options) {
                        $this->assertArrayHasKey('autocomplete', $options);
                        $this->assertEquals(
                            [
                                'route_name' => 'orob2b_frontend_autocomplete_search',
                                'route_parameters' => [
                                    'name' => 'orob2b_product_visibility_limited',
                                ],
                                'selection_template_twig' =>
                                    'OroB2BProductBundle:Product:Autocomplete/autocomplete_selection.html.twig',
                                'componentModule' => 'orob2bproduct/js/app/components/product-autocomplete-component',
                            ],
                            $options['autocomplete']
                        );

                        return true;
                    }
                )
            );

        $this->type->configureOptions($resolver);
    }

    public function testBuildView()
    {
        $product = new Product();
        $product->setSku('sku1');

        $view = new FormView();

        /** @var FormConfigInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $config = $this->getMock('Symfony\Component\Form\FormConfigInterface');
        $config->expects($this->any())
            ->method('getOptions')
            ->willReturn(
                [
                    'product' => null,
                    'product_field' => 'product',
                    'product_holder' => null,
                ]
            );

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())->method('getConfig')->willReturn($config);

        $view->vars['product'] = $product;
        $this->type->buildView($view, $form, []);

        $this->assertEquals(['sku' => 'sku1', 'name' => null], $view->vars['componentOptions']['product']);
    }
}
