<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;

use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectType;

class PriceListSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceListSelectType
     */
    protected $type;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->type = new PriceListSelectType();
    }

    public function testSetDefaultOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->willReturnCallback(
                function (array $options) {
                    $this->assertArrayHasKey('autocomplete_alias', $options);
                    $this->assertEquals('orob2b_pricing_price_list', $options['autocomplete_alias']);

                    $this->assertArrayHasKey('create_form_route', $options);
                    $this->assertEquals('orob2b_pricing_price_list_create', $options['create_form_route']);

                    $this->assertArrayHasKey('configs', $options);
                    $this->assertEquals(
                        ['placeholder' => 'orob2b.pricing.form.choose_price_list'],
                        $options['configs']
                    );
                }
            );

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::NAME, $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(PriceListSelectType::NAME, $this->type->getName());
    }
}
