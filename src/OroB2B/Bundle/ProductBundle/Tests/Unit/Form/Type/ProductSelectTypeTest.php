<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;

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
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                $this->callback(
                    function (array $options) {
                        $this->assertArrayHasKey('autocomplete_alias', $options);
                        $this->assertArrayHasKey('create_form_route', $options);
                        $this->assertArrayHasKey('configs', $options);
                        $this->assertEquals('orob2b_product', $options['autocomplete_alias']);
                        $this->assertEquals('orob2b_product_create', $options['create_form_route']);
                        $this->assertEquals(
                            ['placeholder' => 'orob2b.product.form.choose'],
                            $options['configs']
                        );

                        return true;
                    }
                )
            );

        $this->type->configureOptions($resolver);
    }
}
