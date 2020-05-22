<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\ValidationService;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Provider\EntityCouponsProvider;
use Oro\Bundle\PromotionBundle\Provider\PromotionProvider;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order as OrderStub;
use Oro\Bundle\PromotionBundle\ValidationService\CouponApplicabilityValidationService;
use Oro\Bundle\PromotionBundle\ValidationService\CouponValidationService;
use Oro\Component\Testing\Unit\EntityTrait;

class CouponApplicabilityValidationServiceTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var CouponValidationService|\PHPUnit\Framework\MockObject\MockObject
     */
    private $couponValidationService;

    /**
     * @var PromotionProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $promotionProvider;

    /**
     * @var EntityCouponsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityCouponsProvider;

    /**
     * @var CouponApplicabilityValidationService
     */
    private $couponApplicabilityValidationService;

    protected function setUp(): void
    {
        $this->couponValidationService = $this->createMock(CouponValidationService::class);
        $this->promotionProvider = $this->createMock(PromotionProvider::class);
        $this->entityCouponsProvider = $this->createMock(EntityCouponsProvider::class);

        $this->couponApplicabilityValidationService = new CouponApplicabilityValidationService(
            $this->couponValidationService,
            $this->promotionProvider,
            $this->entityCouponsProvider
        );
    }

    public function testGetViolationsWhenCouponIsNotValid()
    {
        $coupon = new Coupon();
        $customerUser = $this->createMock(CustomerUser::class);
        $entity = (new OrderStub())->setCustomerUser($customerUser);

        $errorMessages = ['oro.order.some_error_message'];
        $this->couponValidationService
            ->expects($this->once())
            ->method('getViolations')
            ->with($coupon, $customerUser)
            ->willReturn($errorMessages);

        $this->assertEquals(
            $errorMessages,
            $this->couponApplicabilityValidationService->getViolations($coupon, $entity)
        );
    }

    public function testGetViolationsWhenCouponAlreadyApplied()
    {
        /** @var Coupon $coupon */
        $coupon = $this->getEntity(Coupon::class, ['id' => 5]);
        $customerUser = $this->createMock(CustomerUser::class);
        $entity = (new OrderStub())->setCustomerUser($customerUser);
        $appliedCoupon = (new AppliedCoupon())->setSourceCouponId(5);
        $entity->addAppliedCoupon($appliedCoupon);

        $this->couponValidationService
            ->expects($this->once())
            ->method('getViolations')
            ->with($coupon, $customerUser)
            ->willReturn([]);

        $this->assertEquals(
            ['oro.promotion.coupon.violation.coupon_already_added'],
            $this->couponApplicabilityValidationService->getViolations($coupon, $entity)
        );
    }

    public function testGetViolationsWhenCouponPromotionAlreadyApplied()
    {
        $promotion = new Promotion();
        $coupon = (new Coupon())->setPromotion($promotion);
        $customerUser = $this->createMock(CustomerUser::class);
        $entity = (new OrderStub())->setCustomerUser($customerUser);

        $this->couponValidationService
            ->expects($this->once())
            ->method('getViolations')
            ->with($coupon, $customerUser)
            ->willReturn([]);

        $this->promotionProvider
            ->expects($this->once())
            ->method('isPromotionApplied')
            ->with($entity, $promotion)
            ->willReturn(true);

        $this->assertEquals(
            ['oro.promotion.coupon.violation.coupon_promotion_already_applied'],
            $this->couponApplicabilityValidationService->getViolations($coupon, $entity)
        );
    }

    public function testGetViolationsWhenCouponPromotionNotApplicable()
    {
        $promotion = new Promotion();
        $coupon = (new Coupon())->setPromotion($promotion);
        $customerUser = $this->createMock(CustomerUser::class);
        $entity = (new OrderStub())->setCustomerUser($customerUser);

        $this->couponValidationService
            ->expects($this->once())
            ->method('getViolations')
            ->with($coupon, $customerUser)
            ->willReturn([]);

        $this->promotionProvider
            ->expects($this->once())
            ->method('isPromotionApplied')
            ->with($entity, $promotion)
            ->willReturn(false);

        $this->promotionProvider
            ->expects($this->once())
            ->method('isPromotionApplicable')
            ->with($entity, $promotion)
            ->willReturn(false);

        $this->assertEquals(
            ['oro.promotion.coupon.violation.coupon_promotion_not_applicable'],
            $this->couponApplicabilityValidationService->getViolations($coupon, $entity)
        );
    }

    public function testGetViolationsWhenCouponIsValid()
    {
        $promotion = new Promotion();
        $coupon = (new Coupon())->setPromotion($promotion);
        $customerUser = $this->createMock(CustomerUser::class);
        $entity = (new OrderStub())->setCustomerUser($customerUser);

        $this->couponValidationService
            ->expects($this->once())
            ->method('getViolations')
            ->with($coupon, $customerUser)
            ->willReturn([]);

        $this->promotionProvider
            ->expects($this->once())
            ->method('isPromotionApplied')
            ->with($entity, $promotion)
            ->willReturn(false);

        $this->promotionProvider
            ->expects($this->once())
            ->method('isPromotionApplicable')
            ->with($entity, $promotion)
            ->willReturn(true);

        $this->assertEmpty($this->couponApplicabilityValidationService->getViolations($coupon, $entity));
    }

    public function testGetViolationsWhenCouponIsValidWithSkipFilters()
    {
        $promotion = new Promotion();
        $coupon = (new Coupon())->setPromotion($promotion);
        $customerUser = $this->createMock(CustomerUser::class);
        $entity = (new OrderStub())->setCustomerUser($customerUser);

        $this->couponValidationService
            ->expects($this->once())
            ->method('getViolations')
            ->with($coupon, $customerUser)
            ->willReturn([]);

        $this->promotionProvider
            ->expects($this->once())
            ->method('isPromotionApplied')
            ->with($entity, $promotion)
            ->willReturn(false);

        $skipFilters = ['SomeFilterClass'];
        $this->promotionProvider
            ->expects($this->once())
            ->method('isPromotionApplicable')
            ->with($entity, $promotion, $skipFilters)
            ->willReturn(true);

        $this->assertEmpty($this->couponApplicabilityValidationService->getViolations($coupon, $entity, $skipFilters));
    }

    public function testGetViolationsWithInvalidEntityPassed()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Argument $entity should implements CustomerOwnerAwareInterface and AppliedCouponsAwareInterface'
        );

        $this->couponApplicabilityValidationService->getViolations(new Coupon(), new \stdClass());
    }
}
