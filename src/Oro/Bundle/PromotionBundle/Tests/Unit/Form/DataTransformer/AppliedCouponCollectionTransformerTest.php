<?php

declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Form\DataTransformer\AppliedCouponCollectionTransformer;
use PHPUnit\Framework\TestCase;

final class AppliedCouponCollectionTransformerTest extends TestCase
{
    private AppliedCouponCollectionTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        $this->transformer = new AppliedCouponCollectionTransformer();
    }

    public function testTransformReturnsNullWhenValueIsNull(): void
    {
        self::assertNull($this->transformer->transform(null));
    }

    public function testTransformReturnsEmptyCollectionWhenCollectionIsEmpty(): void
    {
        $emptyCollection = new ArrayCollection();
        $result = $this->transformer->transform($emptyCollection);

        self::assertSame($emptyCollection, $result);
    }

    public function testTransformReturnsNonCollectionValueAsIs(): void
    {
        $value = 'not a collection';
        $result = $this->transformer->transform($value);

        self::assertSame($value, $result);
    }

    public function testTransformSortsCouponsByPromotionSortOrder(): void
    {
        $appliedPromotion1 = new AppliedPromotion();
        $appliedPromotion1->setPromotionData(['rule' => ['sortOrder' => 30]]);
        $appliedCoupon1 = new AppliedCoupon();
        $appliedCoupon1->setCouponCode('COUPON1');
        $appliedCoupon1->setAppliedPromotion($appliedPromotion1);

        $appliedPromotion2 = new AppliedPromotion();
        $appliedPromotion2->setPromotionData(['rule' => ['sortOrder' => 10]]);
        $appliedCoupon2 = new AppliedCoupon();
        $appliedCoupon2->setCouponCode('COUPON2');
        $appliedCoupon2->setAppliedPromotion($appliedPromotion2);

        $appliedPromotion3 = new AppliedPromotion();
        $appliedPromotion3->setPromotionData(['rule' => ['sortOrder' => 20]]);
        $appliedCoupon3 = new AppliedCoupon();
        $appliedCoupon3->setCouponCode('COUPON3');
        $appliedCoupon3->setAppliedPromotion($appliedPromotion3);

        $collection = new ArrayCollection([$appliedCoupon1, $appliedCoupon2, $appliedCoupon3]);

        $result = $this->transformer->transform($collection);

        self::assertInstanceOf(ArrayCollection::class, $result);
        $resultArray = $result->toArray();
        self::assertCount(3, $resultArray);
        self::assertSame($resultArray, [1 => $resultArray[1], 2 => $resultArray[2], 0 => $resultArray[0]]);
    }

    public function testTransformHandlesCouponsWithoutPromotions(): void
    {
        $appliedPromotion1 = new AppliedPromotion();
        $appliedPromotion1->setPromotionData(['rule' => ['sortOrder' => 20]]);
        $appliedCoupon1 = new AppliedCoupon();
        $appliedCoupon1->setCouponCode('COUPON1');
        $appliedCoupon1->setAppliedPromotion($appliedPromotion1);

        $appliedCoupon2 = new AppliedCoupon();
        $appliedCoupon2->setCouponCode('COUPON2');

        $appliedPromotion3 = new AppliedPromotion();
        $appliedPromotion3->setPromotionData(['rule' => ['sortOrder' => 10]]);
        $appliedCoupon3 = new AppliedCoupon();
        $appliedCoupon3->setCouponCode('COUPON3');
        $appliedCoupon3->setAppliedPromotion($appliedPromotion3);

        $collection = new ArrayCollection([$appliedCoupon1, $appliedCoupon2, $appliedCoupon3]);

        $result = $this->transformer->transform($collection);

        self::assertInstanceOf(ArrayCollection::class, $result);
        self::assertCount(3, $result);
    }

    public function testTransformHandlesCouponsWithMissingSortOrder(): void
    {
        $appliedPromotion1 = new AppliedPromotion();
        $appliedPromotion1->setPromotionData(['rule' => ['sortOrder' => 10]]);
        $appliedCoupon1 = new AppliedCoupon();
        $appliedCoupon1->setCouponCode('COUPON1');
        $appliedCoupon1->setAppliedPromotion($appliedPromotion1);

        $appliedPromotion2 = new AppliedPromotion();
        $appliedPromotion2->setPromotionData(['rule' => []]);
        $appliedCoupon2 = new AppliedCoupon();
        $appliedCoupon2->setCouponCode('COUPON2');
        $appliedCoupon2->setAppliedPromotion($appliedPromotion2);

        $appliedPromotion3 = new AppliedPromotion();
        $appliedPromotion3->setPromotionData(['rule' => ['sortOrder' => 20]]);
        $appliedCoupon3 = new AppliedCoupon();
        $appliedCoupon3->setCouponCode('COUPON3');
        $appliedCoupon3->setAppliedPromotion($appliedPromotion3);

        $collection = new ArrayCollection([$appliedCoupon1, $appliedCoupon2, $appliedCoupon3]);

        $result = $this->transformer->transform($collection);

        self::assertInstanceOf(ArrayCollection::class, $result);
        $resultArray = array_values($result->toArray());
        self::assertCount(3, $resultArray);
        self::assertSame($appliedCoupon2, $resultArray[0]);
        self::assertSame($appliedCoupon1, $resultArray[1]);
        self::assertSame($appliedCoupon3, $resultArray[2]);
    }

    public function testTransformHandlesCouponsWithEqualSortOrder(): void
    {
        $appliedPromotion1 = new AppliedPromotion();
        $appliedPromotion1->setPromotionData(['rule' => ['sortOrder' => 10]]);
        $appliedCoupon1 = new AppliedCoupon();
        $appliedCoupon1->setCouponCode('COUPON1');
        $appliedCoupon1->setAppliedPromotion($appliedPromotion1);

        $appliedPromotion2 = new AppliedPromotion();
        $appliedPromotion2->setPromotionData(['rule' => ['sortOrder' => 10]]);
        $appliedCoupon2 = new AppliedCoupon();
        $appliedCoupon2->setCouponCode('COUPON2');
        $appliedCoupon2->setAppliedPromotion($appliedPromotion2);

        $appliedPromotion3 = new AppliedPromotion();
        $appliedPromotion3->setPromotionData(['rule' => ['sortOrder' => 10]]);
        $appliedCoupon3 = new AppliedCoupon();
        $appliedCoupon3->setCouponCode('COUPON3');
        $appliedCoupon3->setAppliedPromotion($appliedPromotion3);

        $collection = new ArrayCollection([$appliedCoupon1, $appliedCoupon2, $appliedCoupon3]);

        $result = $this->transformer->transform($collection);

        self::assertInstanceOf(ArrayCollection::class, $result);
        self::assertCount(3, $result);
    }

    public function testReverseTransformReturnsValueUnchanged(): void
    {
        $appliedPromotion1 = new AppliedPromotion();
        $appliedPromotion1->setPromotionData(['rule' => ['sortOrder' => 10]]);
        $appliedCoupon1 = new AppliedCoupon();
        $appliedCoupon1->setCouponCode('COUPON1');
        $appliedCoupon1->setAppliedPromotion($appliedPromotion1);

        $appliedPromotion2 = new AppliedPromotion();
        $appliedPromotion2->setPromotionData(['rule' => ['sortOrder' => 20]]);
        $appliedCoupon2 = new AppliedCoupon();
        $appliedCoupon2->setCouponCode('COUPON2');
        $appliedCoupon2->setAppliedPromotion($appliedPromotion2);

        $collection = new ArrayCollection([$appliedCoupon1, $appliedCoupon2]);

        $result = $this->transformer->reverseTransform($collection);

        self::assertSame($collection, $result);
    }

    public function testReverseTransformReturnsNullAsIs(): void
    {
        self::assertNull($this->transformer->reverseTransform(null));
    }
}
