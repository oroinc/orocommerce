<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroAutocompleteType;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductAutocompleteType;

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
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                $this->callback(
                    function (array $options) {
                        $this->assertArrayHasKey('autocomplete', $options);
                        $this->assertEquals(
                            [
                                'alias' => 'orob2b_product',
                                'result_template_twig' =>
                                    'OroB2BProductBundle:Product:Autocomplete/autocomplete_result.html.twig',
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
}
