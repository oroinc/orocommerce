<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroAutocompleteType;
use Oro\Bundle\ProductBundle\Form\Type\ProductAutocompleteType;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductAutocompleteTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductAutocompleteType */
    private $type;

    protected function setUp(): void
    {
        $this->type = new ProductAutocompleteType();
    }

    public function testGetParent()
    {
        $this->assertEquals(OroAutocompleteType::class, $this->type->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->exactly(2))
            ->method('setDefaults')
            ->withConsecutive(
                [
                    $this->callback(function (array $options) {
                        $this->assertArrayHasKey('autocomplete', $options);
                        $this->assertEquals(
                            [
                                'route_name' => 'oro_frontend_autocomplete_search',
                                'route_parameters' => [
                                    'name' => 'oro_product_visibility_limited',
                                ],
                                'selection_template_twig' =>
                                    '@OroProduct/Product/Autocomplete/autocomplete_selection.html.twig',
                                'componentModule' => 'oroproduct/js/app/components/product-autocomplete-component',
                            ],
                            $options['autocomplete']
                        );

                        return true;
                    })
                ],
                [$this->isType('array')]
            );

        $this->type->configureOptions($resolver);
    }

    public function testBuildView()
    {
        $product = new Product();
        $product->setSku('sku1');

        $view = new FormView();

        $config = $this->createMock(FormConfigInterface::class);
        $config->expects($this->any())
            ->method('getOptions')
            ->willReturn(
                [
                    'product' => null,
                    'product_field' => 'product',
                    'product_holder' => null,
                ]
            );

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())->method('getConfig')->willReturn($config);

        $view->vars['product'] = $product;
        $this->type->buildView($view, $form, []);

        $this->assertEquals(['sku' => 'sku1', 'name' => null], $view->vars['componentOptions']['product']);
    }
}
