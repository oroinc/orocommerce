<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PriceListSelectTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var PriceListSelectType */
    private $type;

    protected function setUp(): void
    {
        $this->type = new PriceListSelectType();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->willReturnCallback(function (array $options) {
                $this->assertArrayHasKey('autocomplete_alias', $options);
                $this->assertEquals(PriceListType::class, $options['autocomplete_alias']);

                $this->assertArrayHasKey('create_form_route', $options);
                $this->assertEquals('oro_pricing_price_list_create', $options['create_form_route']);

                $this->assertArrayHasKey('configs', $options);
                $this->assertEquals(['placeholder' => 'oro.pricing.form.choose_price_list'], $options['configs']);
            });

        $this->type->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::class, $this->type->getParent());
    }
}
