<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\OrderBundle\Form\Type\OrderCollectionTableType;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedPromotionCollectionTableType;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedPromotionType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;

class AppliedPromotionCollectionTableTypeTest extends FormIntegrationTestCase
{
    /**
     * @var AppliedPromotionCollectionTableType
     */
    private $formType;

    protected function setUp()
    {
        parent::setUp();
        $this->formType = new AppliedPromotionCollectionTableType();
    }

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
        $form = $this->factory->create($this->formType);

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
        $this->assertEquals(OrderCollectionTableType::class, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(AppliedPromotionCollectionTableType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(AppliedPromotionCollectionTableType::NAME, $this->formType->getBlockPrefix());
    }
}
