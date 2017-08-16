<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\ValidationService;

use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order as OrderStub;
use Oro\Bundle\PromotionBundle\ValidationService\CouponValidationService;
use Oro\Bundle\PromotionBundle\Provider\PromotionProvider;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\ValidationService\CouponApplicabilityValidationService;

class CouponApplicabilityValidationServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CouponValidationService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $couponValidationService;

    /**
     * @var PromotionProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $promotionProvider;

    /**
     * @var CouponApplicabilityValidationService
     */
    private $couponApplicabilityValidationService;

    protected function setUp()
    {
        $this->couponValidationService = $this->createMock(CouponValidationService::class);
        $this->promotionProvider = $this->createMock(PromotionProvider::class);

        $this->couponApplicabilityValidationService = new CouponApplicabilityValidationService(
            $this->couponValidationService,
            $this->promotionProvider
        );
    }

    public function testGetViolationsWhenCouponIsNotValid()
    {
        $coupon = new Coupon();
        $entity = new OrderStub();

        $errorMessages = ['oro.order.some_error_message'];
        $this->couponValidationService
            ->expects($this->once())
            ->method('getViolations')
            ->with($coupon)
            ->willReturn($errorMessages);

        $this->assertEquals(
            $errorMessages,
            $this->couponApplicabilityValidationService->getViolations($coupon, $entity)
        );
    }

    public function testGetViolationsWhenCouponAlreadyApplied()
    {
        $coupon = new Coupon();
        $entity = (new OrderStub())->addAppliedCoupon($coupon);

        $this->couponValidationService
            ->expects($this->once())
            ->method('getViolations')
            ->with($coupon)
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
        $entity = new OrderStub();

        $this->couponValidationService
            ->expects($this->once())
            ->method('getViolations')
            ->with($coupon)
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
        $entity = new OrderStub();

        $this->couponValidationService
            ->expects($this->once())
            ->method('getViolations')
            ->with($coupon)
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
        $entity = new OrderStub();

        $this->couponValidationService
            ->expects($this->once())
            ->method('getViolations')
            ->with($coupon)
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
}
