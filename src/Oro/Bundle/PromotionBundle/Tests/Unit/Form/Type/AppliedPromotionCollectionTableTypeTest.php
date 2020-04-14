<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\OrderBundle\Form\Type\OrderCollectionTableType;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedPromotionCollectionTableType;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedPromotionType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class AppliedPromotionCollectionTableTypeTest extends FormIntegrationTestCase
{
    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    new AppliedPromotionType(),
                ],
                []
            ),
        ];
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create(AppliedPromotionCollectionTableType::class);

        $options = $form->getConfig()->getOptions();

        $this->assertSame(
            'OroPromotionBundle:AppliedPromotion:applied_promotions_edit_table.html.twig',
            $options['template_name']
        );
        $this->assertSame('oroui/js/app/components/view-component', $options['page_component']);
        $this->assertSame(
            ['view' => 'oropromotion/js/app/views/applied-promotion-collection-view'],
            $options['page_component_options']
        );
        $this->assertSame(['class' => 'oro-promotions-collection'], $options['attr']);
        $this->assertSame(AppliedPromotionType::class, $options['entry_type']);
    }

    public function testGetParent()
    {
        $formType = new AppliedPromotionCollectionTableType();
        $this->assertEquals(OrderCollectionTableType::class, $formType->getParent());
    }

    public function testGetName()
    {
        $formType = new AppliedPromotionCollectionTableType();
        $this->assertEquals(AppliedPromotionCollectionTableType::NAME, $formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $formType = new AppliedPromotionCollectionTableType();
        $this->assertEquals(AppliedPromotionCollectionTableType::NAME, $formType->getBlockPrefix());
    }
}
