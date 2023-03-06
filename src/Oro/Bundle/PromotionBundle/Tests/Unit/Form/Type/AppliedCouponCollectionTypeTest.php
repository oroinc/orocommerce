<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\PromotionBundle\Form\Type\AppliedCouponCollectionType;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedCouponType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class AppliedCouponCollectionTypeTest extends FormIntegrationTestCase
{
    private AppliedCouponCollectionType $formType;

    protected function setUp(): void
    {
        $this->formType = new AppliedCouponCollectionType();
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([
                $this->formType,
                new AppliedCouponType()
            ], [])
        ];
    }

    public function testFinishView()
    {
        $form = $this->createMock(FormInterface::class);
        $view = new FormView();
        $entity = new \stdClass();

        $widgetAlias = 'alias';
        $componentView = 'component-view';
        $this->formType->finishView(
            $view,
            $form,
            [
                'entity' => $entity,
                'dialog_widget_alias' => $widgetAlias,
                'page_component_view' => $componentView,
                'page_component_options' => [],
            ]
        );

        $this->assertArrayHasKey('dialogWidgetAlias', $view->vars);
        $this->assertEquals($widgetAlias, $view->vars['dialogWidgetAlias']);
        $this->assertArrayHasKey('entity', $view->vars);
        $this->assertEquals($entity, $view->vars['entity']);
        $this->assertArrayHasKey('data-page-component-view', $view->vars['attr']);
        $this->assertEquals(
            $componentView,
            $view->vars['attr']['data-page-component-view']
        );
        $this->assertArrayHasKey('data-page-component-options', $view->vars['attr']);
        $this->assertEquals(
            json_encode(['dialogWidgetAlias' => $widgetAlias]),
            $view->vars['attr']['data-page-component-options']
        );
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create(AppliedCouponCollectionType::class, null, ['entity' => new \stdClass()]);

        $options = $form->getConfig()->getOptions();

        $this->assertSame(AppliedCouponType::class, $options['entry_type']);
        $this->assertSame('add-coupons-dialog', $options['dialog_widget_alias']);
        $this->assertSame('oropromotion/js/app/views/applied-coupon-collection-view', $options['page_component_view']);
        $this->assertSame([], $options['page_component_options']);
        $this->assertFalse($options['error_bubbling']);
        $this->assertTrue($options['prototype']);
        $this->assertTrue($options['allow_add']);
        $this->assertTrue($options['allow_delete']);
        $this->assertSame('__applied_coupon_collection_item__', $options['prototype_name']);
        $this->assertFalse($options['by_reference']);
    }

    public function testWithoutRequiredOptions()
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "entity" is missing.');
        $this->factory->create(AppliedCouponCollectionType::class);
    }

    public function testGetParent()
    {
        $this->assertEquals(CollectionType::class, $this->formType->getParent());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_promotion_applied_coupon_collection', $this->formType->getBlockPrefix());
    }
}
