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

        $this->assertArraySubset([
            'template_name' => 'OroPromotionBundle:AppliedPromotion:applied_promotions_edit_table.html.twig',
            'page_component' => 'oroui/js/app/components/view-component',
            'page_component_options' => ['view' => 'oropromotion/js/app/views/applied-promotion-collection-view'],
            'attr' => ['class' => 'oro-promotions-collection'],
            'entry_type' => AppliedPromotionType::class,
        ], $form->getConfig()->getOptions());
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
