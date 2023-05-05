<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Mapper;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Mapper\AppliedPromotionMapper;
use Oro\Bundle\PromotionBundle\Model\AppliedPromotionData;
use Oro\Bundle\PromotionBundle\Normalizer\NormalizerInterface;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Component\Testing\Unit\EntityTrait;

class AppliedPromotionMapperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const PROMOTION_ID = 123;
    private const PROMOTION_NAME = 'Order Promotion';
    private const COUPON_ID = 71;
    private const COUPON_CODE = 'summer2010';
    private const DISCOUNT_TYPE = 'order';
    private const DISCOUNT_OPTIONS = ['discount_type' => 'amount'];

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var NormalizerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $normalizer;

    /** @var AppliedPromotionMapper */
    private $appliedPromotionMapper;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->normalizer = $this->createMock(NormalizerInterface::class);
        $this->appliedPromotionMapper = new AppliedPromotionMapper($this->registry, $this->normalizer);
    }

    public function testMapPromotionDataToAppliedPromotionWhenNoCoupons()
    {
        $discountConfiguration = (new DiscountConfiguration())
            ->setType(self::DISCOUNT_TYPE)
            ->setOptions(self::DISCOUNT_OPTIONS);

        $appliedPromotionData = (new AppliedPromotionData())
            ->setDiscountConfiguration($discountConfiguration)
            ->setId(self::PROMOTION_ID)
            ->setRule((new Rule())->setName(self::PROMOTION_NAME));

        $normalizedPromotion = ['rule' => ['name' => self::PROMOTION_NAME]];
        $this->normalizer->expects($this->once())
            ->method('normalize')
            ->with($appliedPromotionData)
            ->willReturn($normalizedPromotion);

        $order = new Order();
        $expectedAppliedPromotionEntity = $this->getEntity(AppliedPromotion::class, [
            'promotionName' => self::PROMOTION_NAME,
            'sourcePromotionId' => self::PROMOTION_ID,
            'configOptions' => self::DISCOUNT_OPTIONS,
            'type' => self::DISCOUNT_TYPE,
            'promotionData' => $normalizedPromotion,
            'order' => $order,
        ]);

        $appliedPromotion = new AppliedPromotion();
        $this->appliedPromotionMapper->mapPromotionDataToAppliedPromotion(
            $appliedPromotion,
            $appliedPromotionData,
            $order
        );

        self::assertEquals($expectedAppliedPromotionEntity, $appliedPromotion);
    }

    public function testMapPromotionDataToAppliedPromotion()
    {
        $discountConfiguration = (new DiscountConfiguration())
            ->setType(self::DISCOUNT_TYPE)
            ->setOptions(self::DISCOUNT_OPTIONS);

        $appliedPromotionData = (new AppliedPromotionData())
            ->setDiscountConfiguration($discountConfiguration)
            ->setId(self::PROMOTION_ID)
            ->setRule((new Rule())->setName(self::PROMOTION_NAME))
            ->addCoupon((new Coupon())->setCode('code'));

        $normalizedPromotion = ['rule' => ['name' => self::PROMOTION_NAME]];
        $this->normalizer->expects($this->once())
            ->method('normalize')
            ->with($appliedPromotionData)
            ->willReturn($normalizedPromotion);

        $order = new Order();
        $appliedCoupon = new AppliedCoupon();
        $appliedCoupon->setCouponCode('code');
        $order->addAppliedCoupon($appliedCoupon);
        $expectedAppliedPromotionEntity = $this->getEntity(AppliedPromotion::class, [
            'promotionName' => self::PROMOTION_NAME,
            'sourcePromotionId' => self::PROMOTION_ID,
            'configOptions' => self::DISCOUNT_OPTIONS,
            'type' => self::DISCOUNT_TYPE,
            'promotionData' => $normalizedPromotion,
            'order' => $order,
            'appliedCoupon' => $appliedCoupon,
        ]);

        $appliedPromotion = new AppliedPromotion();
        $this->appliedPromotionMapper->mapPromotionDataToAppliedPromotion(
            $appliedPromotion,
            $appliedPromotionData,
            $order
        );

        self::assertEquals($expectedAppliedPromotionEntity, $appliedPromotion);
    }

    public function testMapAppliedPromotionToPromotionDataWithoutAppliedCoupon()
    {
        $promotionData = ['rule' => ['name' => self::PROMOTION_NAME]];

        $discountConfiguration = (new DiscountConfiguration())
            ->setType(self::DISCOUNT_TYPE)
            ->setOptions(self::DISCOUNT_OPTIONS);

        $appliedPromotionEntity = $this->getEntity(AppliedPromotion::class, [
            'promotionData' => $promotionData,
            'type' => self::DISCOUNT_TYPE,
            'configOptions' => self::DISCOUNT_OPTIONS
        ]);

        $appliedPromotion = (new AppliedPromotionData());

        $this->normalizer->expects($this->once())
            ->method('denormalize')
            ->with($promotionData)
            ->willReturn($appliedPromotion);

        $expectedAppliedPromotion = (new AppliedPromotionData())
            ->setDiscountConfiguration($discountConfiguration);

        $this->registry->expects($this->never())
            ->method('getManagerForClass');

        self::assertEquals(
            $expectedAppliedPromotion,
            $this->appliedPromotionMapper->mapAppliedPromotionToPromotionData($appliedPromotionEntity)
        );
    }

    public function testMapAppliedPromotionToPromotionDataWhenCouponExistsAndNotChanged()
    {
        $promotionData = ['rule' => ['name' => self::PROMOTION_NAME]];

        $discountConfiguration = (new DiscountConfiguration())
            ->setType(self::DISCOUNT_TYPE)
            ->setOptions(self::DISCOUNT_OPTIONS);

        $appliedCoupon = (new AppliedCoupon())
            ->setCouponCode(self::COUPON_CODE)
            ->setSourceCouponId(self::COUPON_ID)
            ->setSourcePromotionId(self::PROMOTION_ID);

        $appliedPromotionEntity = $this->getEntity(AppliedPromotion::class, [
            'promotionData' => $promotionData,
            'type' => self::DISCOUNT_TYPE,
            'configOptions' => self::DISCOUNT_OPTIONS,
            'appliedCoupon' => $appliedCoupon
        ]);

        $appliedPromotion = (new AppliedPromotionData());

        $this->normalizer->expects($this->once())
            ->method('denormalize')
            ->with($promotionData)
            ->willReturn($appliedPromotion);

        $coupon = $this->getEntity(Coupon::class, [
            'id' => self::COUPON_ID,
            'code' => self::COUPON_CODE,
            'promotion' => $this->getEntity(Promotion::class, ['id' => self::PROMOTION_ID])
        ]);

        $couponManager = $this->createMock(EntityManager::class);
        $couponManager->expects($this->once())
            ->method('find')
            ->with(Coupon::class, self::COUPON_ID)
            ->willReturn($coupon);

        $promotionManager = $this->createMock(EntityManager::class);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap([
                [Promotion::class, $promotionManager],
                [Coupon::class, $couponManager]
            ]);

        $expectedAppliedPromotion = (new AppliedPromotionData())
            ->setDiscountConfiguration($discountConfiguration)
            ->addCoupon($coupon);

        self::assertEquals(
            $expectedAppliedPromotion,
            $this->appliedPromotionMapper->mapAppliedPromotionToPromotionData($appliedPromotionEntity)
        );
    }

    /**
     * @dataProvider couponDataProvider
     */
    public function testMapAppliedPromotionToPromotionDataWhenCouponNotExistsOrChanged(?Coupon $coupon)
    {
        $promotionData = ['rule' => ['name' => self::PROMOTION_NAME]];

        $discountConfiguration = (new DiscountConfiguration())
            ->setType(self::DISCOUNT_TYPE)
            ->setOptions(self::DISCOUNT_OPTIONS);

        $appliedCoupon = (new AppliedCoupon())
            ->setCouponCode(self::COUPON_CODE)
            ->setSourceCouponId(self::COUPON_ID)
            ->setSourcePromotionId(self::PROMOTION_ID);

        $appliedPromotionEntity = $this->getEntity(AppliedPromotion::class, [
            'promotionData' => $promotionData,
            'type' => self::DISCOUNT_TYPE,
            'configOptions' => self::DISCOUNT_OPTIONS,
            'appliedCoupon' => $appliedCoupon
        ]);

        $appliedPromotion = (new AppliedPromotionData());

        $this->normalizer->expects($this->once())
            ->method('denormalize')
            ->with($promotionData)
            ->willReturn($appliedPromotion);

        $couponManager = $this->createMock(EntityManager::class);
        $couponManager->expects($this->once())
            ->method('find')
            ->with(Coupon::class, self::COUPON_ID)
            ->willReturn($coupon);

        $promotion = new Promotion();
        $promotionManager = $this->createMock(EntityManager::class);
        $promotionManager->expects($this->once())
            ->method('find')
            ->with(Promotion::class, self::PROMOTION_ID)
            ->willReturn($promotion);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap([
                [Promotion::class, $promotionManager],
                [Coupon::class, $couponManager]
            ]);

        $newCoupon = $this->getEntity(Coupon::class, [
            'code' => self::COUPON_CODE,
            'promotion' => $promotion
        ]);

        $expectedAppliedPromotion = (new AppliedPromotionData())
            ->setDiscountConfiguration($discountConfiguration)
            ->addCoupon($newCoupon);

        self::assertEquals(
            $expectedAppliedPromotion,
            $this->appliedPromotionMapper->mapAppliedPromotionToPromotionData($appliedPromotionEntity)
        );
    }

    public function couponDataProvider(): array
    {
        return [
            'no coupon found' => [
                'coupon' => null
            ],
            'coupon code was changed' => [
                'coupon' => (new Coupon())->setCode('some code')
            ],
            'coupon promotion was deleted' => [
                'coupon' => (new Coupon())->setCode(self::COUPON_CODE)->setPromotion(null)
            ],
            'coupon was assigned to other promotion' => [
                'coupon' => (new Coupon())->setCode(self::COUPON_CODE)
                    ->setPromotion($this->getEntity(Promotion::class, ['id' => 1]))
            ]
        ];
    }

    public function testMapAppliedPromotionToPromotionDataWhenCouponNotExistsAndPromotionNotExists()
    {
        $promotionData = ['rule' => ['name' => self::PROMOTION_NAME]];

        $discountConfiguration = (new DiscountConfiguration())
            ->setType(self::DISCOUNT_TYPE)
            ->setOptions(self::DISCOUNT_OPTIONS);

        $appliedCoupon = (new AppliedCoupon())
            ->setCouponCode(self::COUPON_CODE)
            ->setSourceCouponId(self::COUPON_ID)
            ->setSourcePromotionId(self::PROMOTION_ID);
        $appliedPromotionEntity = $this->getEntity(AppliedPromotion::class, [
            'promotionData' => $promotionData,
            'type' => self::DISCOUNT_TYPE,
            'configOptions' => self::DISCOUNT_OPTIONS,
            'appliedCoupon' => $appliedCoupon
        ]);

        $this->normalizer->expects($this->once())
            ->method('denormalize')
            ->with($promotionData)
            ->willReturn(new AppliedPromotionData());

        $couponManager = $this->createMock(EntityManager::class);
        $couponManager->expects($this->once())
            ->method('find')
            ->with(Coupon::class, self::COUPON_ID)
            ->willReturn(null);

        $promotionManager = $this->createMock(EntityManager::class);
        $promotionManager->expects($this->once())
            ->method('find')
            ->with(Promotion::class, self::PROMOTION_ID)
            ->willReturn(null);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap([
                [Promotion::class, $promotionManager],
                [Coupon::class, $couponManager]
            ]);

        $promotion = $this->getEntity(Promotion::class, ['id' => self::PROMOTION_ID]);

        $newCoupon = $this->getEntity(Coupon::class, [
            'code' => self::COUPON_CODE,
            'promotion' => $promotion
        ]);
        $promotion->addCoupon($newCoupon);

        $expectedAppliedPromotion = (new AppliedPromotionData())
            ->setDiscountConfiguration($discountConfiguration)
            ->addCoupon($newCoupon);
        $actual = $this->appliedPromotionMapper->mapAppliedPromotionToPromotionData($appliedPromotionEntity);
        self::assertEquals($expectedAppliedPromotion, $actual);
    }
}
