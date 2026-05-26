<?php

declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadMultiplePromotionsCouponData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadMultiplePromotionsWithSortOrderData;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * Tests that applied coupons are correctly sorted by promotion sort order in OrderType form.
 *
 * @dbIsolationPerTest
 */
final class OrderTypeAppliedCouponsSortOrderTest extends WebTestCase
{
    use FormAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadOrders::class,
            LoadMultiplePromotionsCouponData::class,
        ]);
    }

    public function testSubmitRandomlySortedCouponsToOrderWithoutCoupons(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        // Ensure order has no applied coupons initially
        $this->setAppliedCouponsToOrder($order, new ArrayCollection());

        $form = self::createForm(OrderType::class, $order);

        // Prepare coupons data in random order (not sorted by sort order)
        // Order: test-1 (sortOrder: 10), test-2 (sortOrder: -10), test-3 (sortOrder: 20), test-4 (sortOrder: 5)
        $appliedCouponsData = $this->createAppliedCouponsSubmitData();

        $form->submit([
            'customer' => $order->getCustomer()->getId(),
            'currency' => $order->getCurrency(),
            'appliedCoupons' => $appliedCouponsData,
        ], false);

        self::assertTrue($form->isSynchronized(), 'Form should be synchronized after submit');

        $viewData = $form->get('appliedCoupons')->getViewData();

        self::assertInstanceOf(Collection::class, $viewData);
        self::assertCount(4, $viewData);

        $couponCodes = $this->extractCouponCodes($viewData);

        self::assertSame(
            [
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_NEGATIVE_10,
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_5,
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_10,
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_20,
            ],
            $couponCodes,
            'Applied coupons should remain sorted by promotion sort order after form submit'
        );
    }

    public function testSubmitRandomlySortedCouponsToOrderWithCoupons(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $appliedCoupons = $this->createAppliedCouponsInRandomOrder();
        $this->setAppliedCouponsToOrder($order, $appliedCoupons);
        $this->persistAppliedPromotions($order, $appliedCoupons);

        $form = self::createForm(OrderType::class, $order);

        // Prepare coupons data in random order (not sorted by sort order)
        // Order: test-1 (sortOrder: 10), test-2 (sortOrder: -10), test-3 (sortOrder: 20), test-4 (sortOrder: 5)
        $appliedCouponsData = $this->createAppliedCouponsSubmitData();

        $form->submit([
            'customer' => $order->getCustomer()->getId(),
            'currency' => $order->getCurrency(),
            'appliedCoupons' => $appliedCouponsData,
        ], false);

        self::assertTrue($form->isSynchronized());

        $viewData = $form->get('appliedCoupons')->getViewData();

        self::assertInstanceOf(Collection::class, $viewData);
        self::assertCount(4, $viewData);

        $couponCodes = $this->extractCouponCodes($viewData);

        self::assertSame(
            [
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_NEGATIVE_10,
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_5,
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_10,
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_20,
            ],
            $couponCodes,
            'Applied coupons should remain sorted by promotion sort order after form submit'
        );
    }

    public function testSubmitEmptyAppliedCouponsCollection(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $this->setAppliedCouponsToOrder($order, new ArrayCollection());

        $form = self::createForm(OrderType::class, $order);

        self::assertTrue($form->has('appliedCoupons'));

        $viewData = $form->get('appliedCoupons')->getViewData();

        self::assertInstanceOf(Collection::class, $viewData);
        self::assertCount(0, $viewData);
    }

    public function testCouponsSortedBySourcePromotionSortOrderWhenNoAppliedPromotionData(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        // Ensure order has no applied coupons initially
        $this->setAppliedCouponsToOrder($order, new ArrayCollection());

        $form = self::createForm(OrderType::class, $order);

        // Prepare coupons data in random order, but do NOT attach AppliedPromotion (no promotionData)
        // Only couponCode, sourcePromotionId, sourceCouponId are present
        $appliedCouponsData = $this->createAppliedCouponsSubmitData();

        // Remove any AppliedPromotion from the order entity (simulate fresh submission)
        // (No need to persist, as we want to test the fallback logic)

        $form->submit([
            'customer' => $order->getCustomer()->getId(),
            'currency' => $order->getCurrency(),
            'appliedCoupons' => $appliedCouponsData,
        ], false);

        self::assertTrue($form->isSynchronized(), 'Form should be synchronized after submit');

        $viewData = $form->get('appliedCoupons')->getViewData();
        self::assertInstanceOf(Collection::class, $viewData);
        self::assertCount(4, $viewData);

        $couponCodes = $this->extractCouponCodes($viewData);

        // Should be sorted by Promotion sort order: -10, 5, 10, 20
        self::assertSame(
            [
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_NEGATIVE_10,
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_5,
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_10,
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_20,
            ],
            $couponCodes,
            'Coupons should be sorted by source Promotion sort order when no AppliedPromotion data is present'
        );
    }

    public function testCouponsSortedByAppliedPromotionDataSortOrderWhenHasAppliedPromotionData(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        // Create applied coupons with AppliedPromotion entities containing promotionData with sortOrder
        $appliedCoupons = $this->createAppliedCouponsInRandomOrder();
        $this->setAppliedCouponsToOrder($order, $appliedCoupons);
        $this->persistAppliedPromotions($order, $appliedCoupons);

        $form = self::createForm(OrderType::class, $order);

        // Submit coupons data in random order (not sorted by sort order)
        // Order: test-1 (sortOrder: 10), test-2 (sortOrder: -10), test-3 (sortOrder: 20), test-4 (sortOrder: 5)
        $appliedCouponsData = $this->createAppliedCouponsSubmitData();

        $form->submit([
            'customer' => $order->getCustomer()->getId(),
            'currency' => $order->getCurrency(),
            'appliedCoupons' => $appliedCouponsData,
        ], false);

        self::assertTrue($form->isSynchronized(), 'Form should be synchronized after submit');

        $viewData = $form->get('appliedCoupons')->getViewData();

        self::assertInstanceOf(Collection::class, $viewData);
        self::assertCount(4, $viewData);

        $couponCodes = $this->extractCouponCodes($viewData);

        // Should be sorted by AppliedPromotion's promotionData['rule']['sortOrder']: -10, 5, 10, 20
        self::assertSame(
            [
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_NEGATIVE_10,
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_5,
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_10,
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_20,
            ],
            $couponCodes,
            'Applied coupons should be sorted by AppliedPromotion promotionData sortOrder when AppliedPromotion exists'
        );
    }

    /**
     * Verifies that when one coupon is removed from a form submission (while the order already has four
     * applied coupons with corresponding AppliedPromotion entities persisted), the form processes the
     * removal correctly: the deleted coupon is gone and the remaining three coupons are still sorted by
     * their promotion sort order.
     */
    public function testSubmitWithOneCouponRemovedPreservesRemainingCouponsInSortedOrder(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        // Setup: persist all four applied coupons with applied promotions so that sort-order data is
        // available from AppliedPromotion.promotionData (mirroring a previously-saved order).
        $appliedCoupons = $this->createAppliedCouponsInRandomOrder();
        $this->setAppliedCouponsToOrder($order, $appliedCoupons);
        $this->persistAppliedPromotions($order, $appliedCoupons);

        $form = self::createForm(OrderType::class, $order);

        // Submit three coupons in random order — omitting COUPON_FOR_PROMOTION_5 (test-4, sortOrder: 5).
        // Input order: test-1 (10), test-2 (-10), test-3 (20).
        $appliedCouponsData = $this->createAppliedCouponsSubmitDataExcluding(
            LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_5
        );

        $form->submit([
            'customer' => $order->getCustomer()->getId(),
            'currency' => $order->getCurrency(),
            'appliedCoupons' => $appliedCouponsData,
        ], false);

        self::assertTrue($form->isSynchronized(), 'Form should be synchronized after submit');

        $viewData = $form->get('appliedCoupons')->getViewData();

        self::assertInstanceOf(Collection::class, $viewData);
        self::assertCount(3, $viewData, 'Exactly three coupons should remain after removing one');

        $couponCodes = $this->extractCouponCodes($viewData);

        // Remaining coupons should be sorted by their promotion sort order: -10, 10, 20.
        self::assertSame(
            [
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_NEGATIVE_10, // sortOrder: -10
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_10,          // sortOrder: 10
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_20,          // sortOrder: 20
            ],
            $couponCodes,
            'After removing one coupon, the remaining coupons must be sorted by promotion sort order'
        );
    }

    /**
     * Creates submit data for applied coupons in random order (not sorted by sort order).
     * Returns data in format suitable for form submission.
     *
     * @return array<int, array{couponCode: string, sourcePromotionId: int, sourceCouponId: int}>
     */
    private function createAppliedCouponsSubmitData(): array
    {
        /** @var Promotion $promotion10 */
        $promotion10 = $this->getReference(LoadMultiplePromotionsWithSortOrderData::PROMOTION_SORT_ORDER_10);
        /** @var Coupon $coupon10 */
        $coupon10 = $this->getReference(LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_10);

        /** @var Promotion $promotionNeg10 */
        $promotionNeg10 = $this->getReference(
            LoadMultiplePromotionsWithSortOrderData::PROMOTION_SORT_ORDER_NEGATIVE_10
        );
        /** @var Coupon $couponNeg10 */
        $couponNeg10 = $this->getReference(LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_NEGATIVE_10);

        /** @var Promotion $promotion20 */
        $promotion20 = $this->getReference(LoadMultiplePromotionsWithSortOrderData::PROMOTION_SORT_ORDER_20);
        /** @var Coupon $coupon20 */
        $coupon20 = $this->getReference(LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_20);

        /** @var Promotion $promotion5 */
        $promotion5 = $this->getReference(LoadMultiplePromotionsWithSortOrderData::PROMOTION_SORT_ORDER_5);
        /** @var Coupon $coupon5 */
        $coupon5 = $this->getReference(LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_5);

        // Return in random order: 10, -10, 20, 5 (not sorted)
        return [
            [
                'couponCode' => $coupon10->getCode(),
                'sourcePromotionId' => $promotion10->getId(),
                'sourceCouponId' => $coupon10->getId(),
            ],
            [
                'couponCode' => $couponNeg10->getCode(),
                'sourcePromotionId' => $promotionNeg10->getId(),
                'sourceCouponId' => $couponNeg10->getId(),
            ],
            [
                'couponCode' => $coupon20->getCode(),
                'sourcePromotionId' => $promotion20->getId(),
                'sourceCouponId' => $coupon20->getId(),
            ],
            [
                'couponCode' => $coupon5->getCode(),
                'sourcePromotionId' => $promotion5->getId(),
                'sourceCouponId' => $coupon5->getId(),
            ],
        ];
    }

    /**
     * Returns submit data for all four coupons except the one whose coupon-code constant is given.
     * The remaining entries preserve the same random order as {@see createAppliedCouponsSubmitData()}.
     *
     * @return array<int, array{couponCode: string, sourcePromotionId: int, sourceCouponId: int}>
     */
    private function createAppliedCouponsSubmitDataExcluding(string $excludedCouponCode): array
    {
        return array_values(
            array_filter(
                $this->createAppliedCouponsSubmitData(),
                static fn (array $item): bool => $item['couponCode'] !== $excludedCouponCode
            )
        );
    }


    /**
     * Creates applied coupons in a random order (not sorted by sort order).
     * The order is: test-1 (sortOrder: 10), test-2 (sortOrder: -10), test-3 (sortOrder: 20), test-4 (sortOrder: 5)
     */
    private function createAppliedCouponsInRandomOrder(): ArrayCollection
    {
        return new ArrayCollection([
            $this->createAppliedCouponForPromotion(
                LoadMultiplePromotionsWithSortOrderData::PROMOTION_SORT_ORDER_10,
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_10
            ),
            $this->createAppliedCouponForPromotion(
                LoadMultiplePromotionsWithSortOrderData::PROMOTION_SORT_ORDER_NEGATIVE_10,
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_NEGATIVE_10
            ),
            $this->createAppliedCouponForPromotion(
                LoadMultiplePromotionsWithSortOrderData::PROMOTION_SORT_ORDER_20,
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_20
            ),
            $this->createAppliedCouponForPromotion(
                LoadMultiplePromotionsWithSortOrderData::PROMOTION_SORT_ORDER_5,
                LoadMultiplePromotionsCouponData::COUPON_FOR_PROMOTION_5
            ),
        ]);
    }

    private function createAppliedCouponForPromotion(string $promotionReference, string $couponReference): AppliedCoupon
    {
        /** @var Promotion $promotion */
        $promotion = $this->getReference($promotionReference);

        /** @var Coupon $coupon */
        $coupon = $this->getReference($couponReference);

        $appliedPromotion = new AppliedPromotion();
        $appliedPromotion->setType($promotion->getDiscountConfiguration()->getType());
        $appliedPromotion->setPromotionName($promotion->getRule()->getName());
        $appliedPromotion->setSourcePromotionId($promotion->getId());
        $appliedPromotion->setConfigOptions($promotion->getDiscountConfiguration()->getOptions());
        $appliedPromotion->setPromotionData($this->createPromotionData($promotion));

        $appliedCoupon = new AppliedCoupon();
        $appliedCoupon->setCouponCode($coupon->getCode());
        $appliedCoupon->setSourcePromotionId($promotion->getId());
        $appliedCoupon->setSourceCouponId($coupon->getId());
        $appliedCoupon->setAppliedPromotion($appliedPromotion);

        return $appliedCoupon;
    }

    /**
     * Creates normalized promotion data array with rule containing sort order.
     * This mimics the structure created by AppliedPromotionNormalizer.
     */
    private function createPromotionData(Promotion $promotion): array
    {
        return [
            'id' => $promotion->getId(),
            'useCoupons' => $promotion->isUseCoupons(),
            'rule' => [
                'name' => $promotion->getRule()->getName(),
                'expression' => $promotion->getRule()->getExpression(),
                'sortOrder' => $promotion->getRule()->getSortOrder(),
                'isStopProcessing' => $promotion->getRule()->isStopProcessing(),
            ],
            'scopes' => [],
        ];
    }

    private function setAppliedCouponsToOrder(Order $order, Collection $appliedCoupons): void
    {
        foreach ($order->getAppliedCoupons() as $existingCoupon) {
            $order->removeAppliedCoupon($existingCoupon);
        }

        foreach ($appliedCoupons as $appliedCoupon) {
            $appliedCoupon->setOrder($order);
            $order->addAppliedCoupon($appliedCoupon);
        }
    }

    private function persistAppliedPromotions(Order $order, Collection $appliedCoupons): void
    {
        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(AppliedPromotion::class);

        foreach ($appliedCoupons as $appliedCoupon) {
            $entityManager->persist($appliedCoupon->getAppliedPromotion());
            $entityManager->persist($appliedCoupon);
        }

        $entityManager->flush();
        $entityManager->refresh($order);
    }

    /**
     * @param Collection<int, AppliedCoupon> $appliedCoupons
     * @return array<string>
     */
    private function extractCouponCodes(Collection $appliedCoupons): array
    {
        return array_values(
            array_map(
                static fn (AppliedCoupon $appliedCoupon) => $appliedCoupon->getCouponCode(),
                $appliedCoupons->toArray()
            )
        );
    }
}
