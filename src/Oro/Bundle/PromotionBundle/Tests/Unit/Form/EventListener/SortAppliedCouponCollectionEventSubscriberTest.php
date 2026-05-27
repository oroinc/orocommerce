<?php

declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Form\EventListener\SortAppliedCouponCollectionEventSubscriber;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

final class SortAppliedCouponCollectionEventSubscriberTest extends TestCase
{
    private ObjectRepository&MockObject $promotionRepository;
    private SortAppliedCouponCollectionEventSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->promotionRepository = $this->createMock(ObjectRepository::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getRepository')
            ->with(Promotion::class)
            ->willReturn($this->promotionRepository);
        $this->subscriber = new SortAppliedCouponCollectionEventSubscriber($managerRegistry);
    }

    /**
     * When all submitted entries include a sourcePromotionId that maps to an existing AppliedCoupon with
     * AppliedPromotion data, the sort order is taken from promotionData and uasort() reorders the array.
     * Because uasort() preserves original keys, positional assertions must use array_values().
     */
    public function testOnPreSubmitAllWithAppliedPromotionReordersToAscendingSortOrder(): void
    {
        $appliedPromotion1 = new AppliedPromotion();
        $appliedPromotion1->setSourcePromotionId(1);
        $appliedPromotion1->setPromotionData(['rule' => ['sortOrder' => 10]]);

        $appliedPromotion2 = new AppliedPromotion();
        $appliedPromotion2->setSourcePromotionId(2);
        $appliedPromotion2->setPromotionData(['rule' => ['sortOrder' => 5]]);

        $appliedCoupon1 = new AppliedCoupon();
        $appliedCoupon1->setCouponCode('CODE1');
        $appliedCoupon1->setSourcePromotionId(1);
        $appliedCoupon1->setAppliedPromotion($appliedPromotion1);

        $appliedCoupon2 = new AppliedCoupon();
        $appliedCoupon2->setCouponCode('CODE2');
        $appliedCoupon2->setSourcePromotionId(2);
        $appliedCoupon2->setAppliedPromotion($appliedPromotion2);

        $originalCollection = new ArrayCollection([$appliedCoupon1, $appliedCoupon2]);

        // Submitted in descending order (sortOrder 10 first, 5 second) — must be reordered.
        $submittedData = [
            ['couponCode' => 'CODE1', 'sourcePromotionId' => 1],
            ['couponCode' => 'CODE2', 'sourcePromotionId' => 2],
        ];

        $this->promotionRepository
            ->expects(self::never())
            ->method('findBy');

        $form = $this->createMock(FormInterface::class);
        $form
            ->expects(self::any())
            ->method('getData')
            ->willReturn($originalCollection);

        $event = new FormEvent($form, $submittedData);
        $this->subscriber->onPreSubmit($event);

        // uasort() preserves keys; use array_values() to assert by position.
        $sorted = array_values($event->getData());
        self::assertSame('CODE2', $sorted[0]['couponCode'], 'sortOrder 5 must come first');
        self::assertSame('CODE1', $sorted[1]['couponCode'], 'sortOrder 10 must come second');
    }

    /**
     * When the existing applied-coupons collection is empty and submitted data contains sourcePromotionIds,
     * sort orders are fetched from the Promotion repository and uasort() reorders the result.
     */
    public function testOnPreSubmitAllWithoutAppliedPromotionFetchesSortOrderFromRepositoryAndReorders(): void
    {
        // Empty collection: no existing applied coupons → all IDs will be looked up in the repository.
        $originalCollection = new ArrayCollection([]);

        // Submitted in descending order (sortOrder 10 first, 5 second) — must be reordered.
        $submittedData = [
            ['couponCode' => 'CODE1', 'sourcePromotionId' => 1],
            ['couponCode' => 'CODE2', 'sourcePromotionId' => 2],
        ];

        $promotion1 = new Promotion();
        ReflectionUtil::setId($promotion1, 1);
        $rule1 = new Rule();
        $rule1->setSortOrder(10);
        $promotion1->setRule($rule1);

        $promotion2 = new Promotion();
        ReflectionUtil::setId($promotion2, 2);
        $rule2 = new Rule();
        $rule2->setSortOrder(5);
        $promotion2->setRule($rule2);

        $this->promotionRepository
            ->expects(self::once())
            ->method('findBy')
            ->with(['id' => [1, 2]])
            ->willReturn([$promotion1, $promotion2]);

        $form = $this->createMock(FormInterface::class);
        $form
            ->expects(self::any())
            ->method('getData')
            ->willReturn($originalCollection);

        $event = new FormEvent($form, $submittedData);
        $this->subscriber->onPreSubmit($event);

        // uasort() preserves keys; use array_values() to assert by position.
        $sorted = array_values($event->getData());
        self::assertSame('CODE2', $sorted[0]['couponCode'], 'sortOrder 5 must come first');
        self::assertSame('CODE1', $sorted[1]['couponCode'], 'sortOrder 10 must come second');
    }

    /**
     * When some submitted entries have an AppliedPromotion in the existing collection (sort order from
     * promotionData) and others are new (sort order from repository), uasort() merges both sources and
     * reorders correctly.
     */
    public function testOnPreSubmitMixedAppliedPromotionAndSourcePromotionReorders(): void
    {
        $appliedPromotion1 = new AppliedPromotion();
        $appliedPromotion1->setSourcePromotionId(1);
        $appliedPromotion1->setPromotionData(['rule' => ['sortOrder' => 10]]);

        $appliedCoupon1 = new AppliedCoupon();
        $appliedCoupon1->setCouponCode('CODE1');
        $appliedCoupon1->setSourcePromotionId(1);
        $appliedCoupon1->setAppliedPromotion($appliedPromotion1);

        // Only CODE1 is in the existing collection; CODE2 is a new coupon.
        $originalCollection = new ArrayCollection([$appliedCoupon1]);

        // Submitted in descending order (sortOrder 10 first, 5 second) — must be reordered.
        $submittedData = [
            ['couponCode' => 'CODE1', 'sourcePromotionId' => 1],
            ['couponCode' => 'CODE2', 'sourcePromotionId' => 2],
        ];

        $promotion2 = new Promotion();
        ReflectionUtil::setId($promotion2, 2);
        $rule2 = new Rule();
        $rule2->setSortOrder(5);
        $promotion2->setRule($rule2);

        // Only the new promotion ID (2) should be queried; ID 1 comes from appliedPromotion1.
        $this->promotionRepository
            ->expects(self::once())
            ->method('findBy')
            ->with(['id' => [2]])
            ->willReturn([$promotion2]);

        $form = $this->createMock(FormInterface::class);
        $form
            ->expects(self::any())
            ->method('getData')
            ->willReturn($originalCollection);

        $event = new FormEvent($form, $submittedData);
        $this->subscriber->onPreSubmit($event);

        // uasort() preserves keys; use array_values() to assert by position.
        $sorted = array_values($event->getData());
        self::assertSame('CODE2', $sorted[0]['couponCode'], 'sortOrder 5 must come first');
        self::assertSame('CODE1', $sorted[1]['couponCode'], 'sortOrder 10 must come second');
    }

    public function testOnPreSubmitWithEmptyData(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())
            ->method('getData')
            ->willReturn(new ArrayCollection());
        $event = new FormEvent($form, []);

        $this->subscriber->onPreSubmit($event);

        self::assertSame([], $event->getData());
    }

    public function testOnPreSubmitCouponNotInOriginalCollectionAndNoSourcePromotionId(): void
    {
        $appliedCoupon = new AppliedCoupon();
        $appliedCoupon->setCouponCode('CODE1');
        $appliedCoupon->setSourcePromotionId(1);
        $originalCollection = new ArrayCollection([$appliedCoupon]);
        $submittedData = [
            ['couponCode' => 'CODE2'],
        ];

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())
            ->method('getData')
            ->willReturn($originalCollection);
        $event = new FormEvent($form, $submittedData);

        $this->subscriber->onPreSubmit($event);

        self::assertSame('CODE2', $event->getData()[0]['couponCode']);
    }

    public function testOnPreSubmitPromotionNotFound(): void
    {
        $appliedCoupon = new AppliedCoupon();
        $appliedCoupon->setCouponCode('CODE1');
        $appliedCoupon->setSourcePromotionId(1);
        $originalCollection = new ArrayCollection([$appliedCoupon]);
        $submittedData = [
            ['couponCode' => 'CODE1', 'sourcePromotionId' => 42],
        ];

        $this->promotionRepository
            ->expects(self::once())
            ->method('findBy')
            ->with(['id' => [42]])
            ->willReturn([]);
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())
            ->method('getData')
            ->willReturn($originalCollection);
        $event = new FormEvent($form, $submittedData);

        $this->subscriber->onPreSubmit($event);

        self::assertSame('CODE1', $event->getData()[0]['couponCode']);
    }

    public function testOnPreSubmitWithDuplicateCouponCodes(): void
    {
        $appliedPromotion = new AppliedPromotion();
        $appliedPromotion->setSourcePromotionId(1);
        $appliedPromotion->setPromotionData([
            'rule' => [
                'sortOrder' => 10
            ]
        ]);
        $appliedCoupon = new AppliedCoupon();
        $appliedCoupon->setCouponCode('CODE1');
        $appliedCoupon->setSourcePromotionId(1);
        $appliedCoupon->setAppliedPromotion($appliedPromotion);
        $originalCollection = new ArrayCollection([$appliedCoupon]);
        $submittedData = [
            ['couponCode' => 'CODE1'],
            ['couponCode' => 'CODE1'],
        ];

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())
            ->method('getData')
            ->willReturn($originalCollection);
        $event = new FormEvent($form, $submittedData);

        $this->subscriber->onPreSubmit($event);

        $sorted = $event->getData();
        self::assertCount(2, $sorted);
        self::assertSame('CODE1', $sorted[0]['couponCode']);
        self::assertSame('CODE1', $sorted[1]['couponCode']);
    }

    /**
     * onPreSetData sorts a non-empty collection by the sort order stored in each
     * AppliedCoupon's AppliedPromotion promotionData.
     * Because uasort() preserves original collection keys, positional assertions must use array_values().
     */
    public function testOnPreSetDataSortsCollectionByAppliedPromotionSortOrder(): void
    {
        $appliedPromotion1 = new AppliedPromotion();
        $appliedPromotion1->setSourcePromotionId(1);
        $appliedPromotion1->setPromotionData(['rule' => ['sortOrder' => 10]]);

        $appliedPromotion2 = new AppliedPromotion();
        $appliedPromotion2->setSourcePromotionId(2);
        $appliedPromotion2->setPromotionData(['rule' => ['sortOrder' => 5]]);

        $appliedCoupon1 = new AppliedCoupon();
        $appliedCoupon1->setCouponCode('CODE1');
        $appliedCoupon1->setSourcePromotionId(1);
        $appliedCoupon1->setAppliedPromotion($appliedPromotion1);

        $appliedCoupon2 = new AppliedCoupon();
        $appliedCoupon2->setCouponCode('CODE2');
        $appliedCoupon2->setSourcePromotionId(2);
        $appliedCoupon2->setAppliedPromotion($appliedPromotion2);

        // Collection in descending order (sortOrder 10 first, 5 second) — must be reordered.
        $collection = new ArrayCollection([$appliedCoupon1, $appliedCoupon2]);

        $this->promotionRepository->expects(self::never())->method('findBy');

        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $collection);
        $this->subscriber->onPreSetData($event);

        // uasort() preserves keys; use array_values() to assert by position.
        $sorted = array_values($event->getData()->toArray());
        self::assertSame('CODE2', $sorted[0]->getCouponCode(), 'sortOrder 5 must come first');
        self::assertSame('CODE1', $sorted[1]->getCouponCode(), 'sortOrder 10 must come second');
    }

    /**
     * onPreSetData returns immediately when the collection is empty, leaving the event data unchanged.
     */
    public function testOnPreSetDataWithEmptyCollectionDoesNothing(): void
    {
        $collection = new ArrayCollection([]);

        $this->promotionRepository->expects(self::never())->method('findBy');

        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $collection);
        $this->subscriber->onPreSetData($event);

        self::assertSame($collection, $event->getData(), 'Empty collection must not be replaced');
    }

    /**
     * onPreSetData returns immediately when the event data is not a Collection instance (e.g. null),
     * leaving the event data unchanged.
     */
    public function testOnPreSetDataWithNonCollectionDataDoesNothing(): void
    {
        $this->promotionRepository->expects(self::never())->method('findBy');

        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, null);
        $this->subscriber->onPreSetData($event);

        self::assertNull($event->getData(), 'Null data must remain null');
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertSame(
            [
                FormEvents::PRE_SET_DATA => ['onPreSetData', 300],
                FormEvents::PRE_SUBMIT => ['onPreSubmit', 300],
            ],
            SortAppliedCouponCollectionEventSubscriber::getSubscribedEvents()
        );
    }
}
