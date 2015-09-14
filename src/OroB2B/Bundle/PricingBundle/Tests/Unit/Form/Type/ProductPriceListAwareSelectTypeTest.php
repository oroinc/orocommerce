<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\PricingBundle\Form\Type\ProductPriceListAwareSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;

class ProductPriceListAwareSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductPriceListAwareSelectType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new ProductPriceListAwareSelectType();
    }

    public function testGetName()
    {
        $this->assertEquals(ProductPriceListAwareSelectType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(ProductSelectType::NAME, $this->type->getParent());
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                $this->callback(
                    function (array $options) {
                        $this->assertArrayHasKey('autocomplete_alias', $options);
                        $this->assertEquals(
                            'orob2b_pricing_price_list_aware_products_list',
                            $options['autocomplete_alias']
                        );

                        return true;
                    }
                )
            );

        $this->type->configureOptions($resolver);
    }
}
