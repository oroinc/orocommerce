<?php

declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Form\DataTransformer\AppliedCouponCollectionTransformer;
use Oro\Bundle\PromotionBundle\Form\EventListener\SortAppliedCouponCollectionEventSubscriber;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedCouponCollectionType;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedCouponType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class AppliedCouponCollectionTypeTest extends FormIntegrationTestCase
{
    private AppliedCouponCollectionTransformer&MockObject $transformer;
    private SortAppliedCouponCollectionEventSubscriber $eventSubscriber;
    private AppliedCouponCollectionType $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->transformer = $this->createMock(AppliedCouponCollectionTransformer::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->eventSubscriber = new SortAppliedCouponCollectionEventSubscriber($managerRegistry);
        $this->formType = new AppliedCouponCollectionType($this->transformer);
        $this->formType->setSortAppliedCouponCollectionEventSubscriber($this->eventSubscriber);
        parent::setUp();
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([
                $this->formType,
                new AppliedCouponType()
            ], [])
        ];
    }

    /**
     * When the sort subscriber is set, buildForm must register only the event subscriber.
     * The view transformer must NOT be added (it is an either/or design).
     */
    public function testBuildFormWithSubscriberAddsEventSubscriberOnly(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::never())
            ->method('addViewTransformer');
        $builder->expects(self::once())
            ->method('addEventSubscriber')
            ->with($this->eventSubscriber);

        $this->formType->buildForm($builder, []);
    }

    /**
     * When no sort subscriber is set (nullable setter left as null),
     * buildForm must register only the view transformer.
     */
    public function testBuildFormWithoutSubscriberAddsViewTransformerOnly(): void
    {
        $formType = new AppliedCouponCollectionType($this->transformer);
        // deliberately do NOT call setSortAppliedCouponCollectionEventSubscriber()

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('addViewTransformer')
            ->with($this->transformer);
        $builder->expects(self::never())
            ->method('addEventSubscriber');

        $formType->buildForm($builder, []);
    }

    public function testFinishView(): void
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

        self::assertArrayHasKey('dialogWidgetAlias', $view->vars);
        self::assertEquals($widgetAlias, $view->vars['dialogWidgetAlias']);
        self::assertArrayHasKey('entity', $view->vars);
        self::assertEquals($entity, $view->vars['entity']);
        self::assertArrayHasKey('data-page-component-view', $view->vars['attr']);
        self::assertEquals(
            $componentView,
            $view->vars['attr']['data-page-component-view']
        );
        self::assertArrayHasKey('data-page-component-options', $view->vars['attr']);
        self::assertEquals(
            json_encode(['dialogWidgetAlias' => $widgetAlias]),
            $view->vars['attr']['data-page-component-options']
        );
    }

    public function testDefaultOptions(): void
    {
        $form = $this->factory->create(
            AppliedCouponCollectionType::class,
            new ArrayCollection(),
            ['entity' => new \stdClass()]
        );

        $options = $form->getConfig()->getOptions();

        self::assertSame(AppliedCouponType::class, $options['entry_type']);
        self::assertSame('add-coupons-dialog', $options['dialog_widget_alias']);
        self::assertSame('oropromotion/js/app/views/applied-coupon-collection-view', $options['page_component_view']);
        self::assertSame([], $options['page_component_options']);
        self::assertFalse($options['error_bubbling']);
        self::assertTrue($options['prototype']);
        self::assertTrue($options['allow_add']);
        self::assertTrue($options['allow_delete']);
        self::assertSame('__applied_coupon_collection_item__', $options['prototype_name']);
        self::assertFalse($options['by_reference']);
    }

    public function testWithoutRequiredOptions(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "entity" is missing.');
        $this->factory->create(AppliedCouponCollectionType::class);
    }

    public function testGetParent(): void
    {
        self::assertEquals(CollectionType::class, $this->formType->getParent());
    }

    public function testGetBlockPrefix(): void
    {
        self::assertEquals('oro_promotion_applied_coupon_collection', $this->formType->getBlockPrefix());
    }
}
