<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\ValidationService;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Manager\CouponUsageManager;
use Oro\Bundle\PromotionBundle\ValidationService\CouponValidationService;
use Oro\Component\Testing\Unit\EntityTrait;

class CouponValidationServiceTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var CouponUsageManager|\PHPUnit\Framework\MockObject\MockObject */
    private $couponUsageManager;

    /** @var CouponValidationService */
    private $couponValidationService;

    protected function setUp(): void
    {
        $this->couponUsageManager = $this->createMock(CouponUsageManager::class);
        $this->couponValidationService = new CouponValidationService($this->couponUsageManager);
    }

    public function testGetViolations()
    {
        $coupon = new Coupon();
        $coupon->setEnabled(true);
        $coupon->setPromotion(new Promotion());
        $coupon->setValidUntil(new \DateTime('+3 days', new \DateTimeZone('UTC')));
        $coupon->setUsesPerCoupon(10);

        $this->couponUsageManager->expects($this->once())
            ->method('getCouponUsageCount')
            ->willReturn(5);

        $this->assertEmpty($this->couponValidationService->getViolations($coupon));
    }

    public function testGetViolationsNotStartedCoupon()
    {
        $coupon = new Coupon();
        $coupon->setEnabled(true);
        $coupon->setPromotion(new Promotion());
        $coupon->setValidFrom(new \DateTime('+3 days', new \DateTimeZone('UTC')));

        $violations = $this->couponValidationService->getViolations($coupon);

        $this->assertCount(1, $violations);
        $this->assertContains('oro.promotion.coupon.violation.not_started', $violations);
    }

    public function testGetViolationsExpiredCoupon()
    {
        $coupon = new Coupon();
        $coupon->setEnabled(true);
        $coupon->setPromotion(new Promotion());
        $coupon->setValidUntil(new \DateTime('-3 days', new \DateTimeZone('UTC')));

        $violations = $this->couponValidationService->getViolations($coupon);

        $this->assertCount(1, $violations);
        $this->assertContains('oro.promotion.coupon.violation.expired', $violations);
    }

    public function testGetViolationsDisabled()
    {
        $coupon = new Coupon();
        $coupon->setPromotion(new Promotion());
        $coupon->setValidUntil(new \DateTime('+3 days', new \DateTimeZone('UTC')));

        $violations = $this->couponValidationService->getViolations($coupon);

        $this->assertCount(1, $violations);
        $this->assertContains('oro.promotion.coupon.violation.disabled', $violations);
    }

    public function testGetViolationsCouponWithoutPromotion()
    {
        $coupon = new Coupon();
        $coupon->setEnabled(true);
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
        $coupon->setEnabled(true);
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

    public function testGetViolationsCustomerUserUsageLimitExceededCoupon()
    {
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 42, 'email' => 'test@example.com']);

        $coupon = new Coupon();
        $coupon->setEnabled(true);
        $coupon->setPromotion(new Promotion());
        $coupon->setValidUntil(new \DateTime('+3 days', new \DateTimeZone('UTC')));
        $coupon->setUsesPerCoupon(10);
        $coupon->setUsesPerPerson(5);

        $this->couponUsageManager->expects($this->once())
            ->method('getCouponUsageCountByCustomerUser')
            ->with($coupon, $customerUser)
            ->willReturn(5);

        $violations = $this->couponValidationService->getViolations($coupon, $customerUser);

        $this->assertCount(1, $violations);
        $this->assertContains('oro.promotion.coupon.violation.customer_user_usage_limit_exceeded', $violations);
    }

    public function testGetViolationsSeveralErrors()
    {
        $coupon = new Coupon();
        $coupon->setEnabled(true);
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

    /**
     * @dataProvider isValidDataProvider
     */
    public function testIsValid(Coupon $coupon, bool $isValid)
    {
        $this->assertEquals($isValid, $this->couponValidationService->isValid($coupon));
    }

    public function isValidDataProvider(): array
    {
        return [
            'not valid' => [
                'coupon' => (new Coupon())->setValidUntil(new \DateTime('-3 days', new \DateTimeZone('UTC'))),
                'isValid' => false
            ],
            'valid' => [
                'coupon' => (new Coupon())
                    ->setEnabled(true)
                    ->setPromotion(new Promotion())
                    ->setUsesPerCoupon(null)
                    ->setUsesPerPerson(null)
                    ->setValidUntil(new \DateTime('+3 days', new \DateTimeZone('UTC'))),
                'isValid' => true
            ]
        ];
    }
}
