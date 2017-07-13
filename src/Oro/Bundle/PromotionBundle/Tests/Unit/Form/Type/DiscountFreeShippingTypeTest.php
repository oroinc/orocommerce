<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\PromotionBundle\Form\Type\DiscountFreeShippingType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class DiscountFreeShippingTypeTest extends FormIntegrationTestCase
{
    /**
     * @var DiscountFreeShippingType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();
        $this->formType = new DiscountFreeShippingType();
    }

    public function testGetName()
    {
        $this->assertEquals(DiscountFreeShippingType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(DiscountFreeShippingType::NAME, $this->formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->formType->getParent());
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create($this->formType, null);

        $options = $form->getConfig()->getOptions();
        $this->assertArraySubset([
            'label' => 'oro.promotion.discount.free_shipping.label',
            'empty_data' => null,
            'empty_value' => 'oro.promotion.discount.free_shipping.no',
            'required' => false,
        ], $options);

        $this->assertArrayHasKey('choices', $options);

        $this->assertArrayHasKey(ShippingDiscount::APPLY_TO_ITEMS, $options['choices']);
        $this->assertArrayHasKey(ShippingDiscount::APPLY_TO_ORDER, $options['choices']);
    }
}
