<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\PromotionBundle\Form\Type\CouponAutocompleteType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CouponAutocompleteTypeTest extends FormIntegrationTestCase
{
    /**
     * @var CouponAutocompleteType
     */
    private $formType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formType = new CouponAutocompleteType();
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::class, $this->formType->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertArrayHasKey('autocomplete_alias', $options);
        $this->assertEquals('oro_coupon', $options['autocomplete_alias']);
        $this->assertArrayHasKey('grid_name', $options);
        $this->assertEquals('promotion-coupons-select-grid', $options['grid_name']);
        $this->assertArrayHasKey('label', $options);
        $this->assertEquals('oro.promotion.coupon.entity_label', $options['label']);
        $this->assertArrayHasKey('configs', $options);
        $this->assertEquals([
            'placeholder' => 'oro.promotion.coupon.autocomplete.placeholder',
            'result_template_twig' => '@OroPromotion/Coupon/Autocomplete/result.html.twig',
            'selection_template_twig' => '@OroPromotion/Coupon/Autocomplete/selection.html.twig',
        ], $options['configs']);
    }
}
