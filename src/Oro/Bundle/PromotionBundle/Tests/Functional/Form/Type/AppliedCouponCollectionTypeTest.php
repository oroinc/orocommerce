<?php

declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Form\DataTransformer\AppliedCouponCollectionTransformer;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedCouponCollectionType;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedCouponType;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

final class AppliedCouponCollectionTypeTest extends WebTestCase
{
    use FormAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testFormTypeIsRegistered(): void
    {
        $form = self::createForm(
            AppliedCouponCollectionType::class,
            null,
            ['entity' => new Order()]
        );

        self::assertInstanceOf(
            AppliedCouponCollectionType::class,
            $form->getConfig()->getType()->getInnerType()
        );
    }

    public function testFormTypeHasTransformer(): void
    {
        $form = self::createForm(
            AppliedCouponCollectionType::class,
            null,
            ['entity' => new Order()]
        );

        $transformers = $form->getConfig()->getViewTransformers();
        self::assertCount(1, $transformers);
        self::assertInstanceOf(AppliedCouponCollectionTransformer::class, $transformers[0]);
    }

    public function testFormTypeTransformerSortsCoupons(): void
    {
        $appliedCoupon1 = $this->createAppliedCouponWithSortOrder(30, 'coupon1');
        $appliedCoupon2 = $this->createAppliedCouponWithSortOrder(10, 'coupon2');
        $appliedCoupon3 = $this->createAppliedCouponWithSortOrder(20, 'coupon3');

        $data = new ArrayCollection([$appliedCoupon1, $appliedCoupon2, $appliedCoupon3]);

        $form = self::createForm(
            AppliedCouponCollectionType::class,
            $data,
            ['entity' => new Order()]
        );

        $viewData = $form->getViewData();

        self::assertInstanceOf(ArrayCollection::class, $viewData);
        $sortedArray = $viewData->toArray();

        self::assertCount(3, $sortedArray);
        self::assertSame($appliedCoupon2, $sortedArray[1], 'Coupon with sort order 10 should be first');
        self::assertSame($appliedCoupon3, $sortedArray[2], 'Coupon with sort order 20 should be second');
        self::assertSame($appliedCoupon1, $sortedArray[0], 'Coupon with sort order 30 should be third');
    }

    public function testFormTypeWithEmptyCollection(): void
    {
        $data = new ArrayCollection();

        $form = self::createForm(
            AppliedCouponCollectionType::class,
            $data,
            ['entity' => new Order()]
        );

        $viewData = $form->getViewData();

        self::assertInstanceOf(ArrayCollection::class, $viewData);
        self::assertCount(0, $viewData);
    }

    public function testFormTypeConfigOptions(): void
    {
        $entity = new Order();
        $form = self::createForm(
            AppliedCouponCollectionType::class,
            null,
            ['entity' => $entity]
        );

        self::assertFormOptions($form, [
            'entry_type' => AppliedCouponType::class,
            'dialog_widget_alias' => 'add-coupons-dialog',
            'page_component_view' => 'oropromotion/js/app/views/applied-coupon-collection-view',
            'page_component_options' => [],
            'error_bubbling' => false,
            'prototype' => true,
            'allow_add' => true,
            'allow_delete' => true,
            'prototype_name' => '__applied_coupon_collection_item__',
            'by_reference' => false
        ]);
    }

    public function testFormTypeRequiresEntityOption(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "entity" is missing.');

        self::createForm(AppliedCouponCollectionType::class);
    }

    public function testFormViewVariables(): void
    {
        $entity = new Order();
        $form = self::createForm(
            AppliedCouponCollectionType::class,
            null,
            ['entity' => $entity]
        );

        $view = $form->createView();

        self::assertArrayHasKey('dialogWidgetAlias', $view->vars);
        self::assertEquals('add-coupons-dialog', $view->vars['dialogWidgetAlias']);
        self::assertArrayHasKey('entity', $view->vars);
        self::assertSame($entity, $view->vars['entity']);
        self::assertArrayHasKey('attr', $view->vars);
        self::assertArrayHasKey('data-page-component-view', $view->vars['attr']);
        self::assertEquals(
            'oropromotion/js/app/views/applied-coupon-collection-view',
            $view->vars['attr']['data-page-component-view']
        );
        self::assertEquals(
            json_encode(['dialogWidgetAlias' => 'add-coupons-dialog']),
            $view->vars['attr']['data-page-component-options']
        );
    }

    private function createAppliedCouponWithSortOrder(int $sortOrder, string $couponCode): AppliedCoupon
    {
        $appliedPromotion = new AppliedPromotion();
        $appliedPromotion->setPromotionData([
            'rule' => [
                'sortOrder' => $sortOrder,
                'name' => 'Test Promotion'
            ]
        ]);

        $appliedCoupon = new AppliedCoupon();
        $appliedCoupon->setCouponCode($couponCode);
        $appliedCoupon->setAppliedPromotion($appliedPromotion);

        return $appliedCoupon;
    }
}
