<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\ValidationService;

use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Manager\CouponUsageManager;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\ValidationService\CouponValidationService;

class CouponValidationServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CouponUsageManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $couponUsageManager;

    /**
     * @var CouponValidationService
     */
    private $couponValidationService;

    protected function setUp()
    {
        $this->couponUsageManager = $this->createMock(CouponUsageManager::class);
        $this->couponValidationService = new CouponValidationService($this->couponUsageManager);
    }

    public function testGetViolations()
    {
        $coupon = new Coupon();
        $coupon->setPromotion(new Promotion());
        $coupon->setValidUntil(new \DateTime('+3 days', new \DateTimeZone('UTC')));
        $coupon->setUsesPerCoupon(10);

        $this->couponUsageManager->expects($this->once())
            ->method('getCouponUsageCount')
            ->willReturn(5);

        $this->assertEmpty($this->couponValidationService->getViolations($coupon));
    }

    public function testGetViolationsExpiredCoupon()
    {
        $coupon = new Coupon();
        $coupon->setPromotion(new Promotion());
        $coupon->setValidUntil(new \DateTime('-3 days', new \DateTimeZone('UTC')));

        $violations = $this->couponValidationService->getViolations($coupon);

        $this->assertCount(1, $violations);
        $this->assertContains('oro.promotion.coupon.violation.expired', $violations);
    }

    public function testGetViolationsCouponWithoutPromotion()
    {
        $coupon = new Coupon();
        $coupon->setValidUntil(new \DateTime('+3 days', new \DateTimeZone('UTC')));
        $coupon->setUsesPerCoupon(10);

        $this->couponUsageManager->expects($this->once())
            ->method('getCouponUsageCount')
            ->willReturn(1);


        $violations = $this->couponValidationService->getViolations($coupon);

        $this->assertCount(1, $violations);
        $this->assertContains('oro.promotion.coupon.violation.absent_promotion', $violations);
    }

    public function testGetViolationsUsageLimitExceededCoupon()
    {
        $coupon = new Coupon();
        $coupon->setPromotion(new Promotion());
        $coupon->setValidUntil(new \DateTime('+3 days', new \DateTimeZone('UTC')));
        $coupon->setUsesPerCoupon(10);

        $this->couponUsageManager->expects($this->once())
            ->method('getCouponUsageCount')
            ->willReturn(10);

        $violations = $this->couponValidationService->getViolations($coupon);

        $this->assertCount(1, $violations);
        $this->assertContains('oro.promotion.coupon.violation.usage_limit_exceeded', $violations);
    }

    public function testGetViolationsSeveralErrors()
    {
        $coupon = new Coupon();
        $coupon->setValidUntil(new \DateTime('-3 days', new \DateTimeZone('UTC')));
        $coupon->setUsesPerCoupon(10);

        $this->couponUsageManager->expects($this->once())
            ->method('getCouponUsageCount')
            ->willReturn(10);

        $violations = $this->couponValidationService->getViolations($coupon);

        $this->assertCount(3, $violations);
        $this->assertContains('oro.promotion.coupon.violation.expired', $violations);
        $this->assertContains('oro.promotion.coupon.violation.usage_limit_exceeded', $violations);
        $this->assertContains('oro.promotion.coupon.violation.absent_promotion', $violations);
    }
}
