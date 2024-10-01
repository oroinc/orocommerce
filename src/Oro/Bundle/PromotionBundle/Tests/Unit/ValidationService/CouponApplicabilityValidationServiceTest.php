<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\ValidationService;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Model\PromotionAwareEntityHelper;
use Oro\Bundle\PromotionBundle\Provider\EntityCouponsProvider;
use Oro\Bundle\PromotionBundle\Provider\PromotionProvider;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order as OrderStub;
use Oro\Bundle\PromotionBundle\ValidationService\CouponApplicabilityValidationService;
use Oro\Bundle\PromotionBundle\ValidationService\CouponValidatorInterface;
use Oro\Component\Testing\ReflectionUtil;

class CouponApplicabilityValidationServiceTest extends \PHPUnit\Framework\TestCase
{
    /** @var PromotionProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $promotionProvider;

    /** @var EntityCouponsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entityCouponsProvider;

    /** @var PromotionAwareEntityHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $promotionAwareHelper;

    /** @var CouponValidatorInterface|\PHPUnit\Framework\MockObject\MockObject  */
    private $couponValidator;

    /** @var CouponApplicabilityValidationService */
    private $couponApplicabilityValidationService;

    #[\Override]
    protected function setUp(): void
    {
        $this->promotionProvider = $this->createMock(PromotionProvider::class);
        $this->entityCouponsProvider = $this->createMock(EntityCouponsProvider::class);
        $this->promotionAwareHelper = $this->createMock(PromotionAwareEntityHelper::class);
        $this->couponValidator = $this->createMock(CouponValidatorInterface::class);

        $this->couponApplicabilityValidationService = new CouponApplicabilityValidationService(
            $this->promotionProvider,
            $this->entityCouponsProvider,
            $this->promotionAwareHelper,
            [$this->couponValidator]
        );
    }

    public function testGetViolationsWhenCouponIsNotValid(): void
    {
        $coupon = new Coupon();
        $customerUser = $this->createMock(CustomerUser::class);
        $entity = (new OrderStub())->setCustomerUser($customerUser);

        $errorMessages = ['oro.order.some_error_message'];

        $this->couponValidator->expects(self::once())
            ->method('getViolationMessages')
            ->with($coupon, $entity)
            ->willReturn($errorMessages);

        $this->promotionAwareHelper->expects($this->once())->method('isCouponAware')->willReturn(true);
        $this->assertEquals(
            $errorMessages,
            $this->couponApplicabilityValidationService->getViolations($coupon, $entity)
        );
    }

    public function testGetViolationsWhenCouponAlreadyApplied(): void
    {
        $coupon = new Coupon();
        ReflectionUtil::setId($coupon, 5);
        $customerUser = $this->createMock(CustomerUser::class);
        $entity = (new OrderStub())->setCustomerUser($customerUser);
        $appliedCoupon = (new AppliedCoupon())->setSourceCouponId(5);
        $entity->addAppliedCoupon($appliedCoupon);

        $this->couponValidator->expects(self::once())
            ->method('getViolationMessages')
            ->with($coupon, $entity)
            ->willReturn([]);

        $this->promotionAwareHelper->expects($this->once())->method('isCouponAware')->willReturn(true);
        $this->assertEquals(
            ['oro.promotion.coupon.violation.coupon_already_added'],
            $this->couponApplicabilityValidationService->getViolations($coupon, $entity)
        );
    }

    public function testGetViolationsWhenCouponPromotionAlreadyApplied(): void
    {
        $promotion = new Promotion();
        $coupon = (new Coupon())->setPromotion($promotion);
        $customerUser = $this->createMock(CustomerUser::class);
        $entity = (new OrderStub())->setCustomerUser($customerUser);

        $this->couponValidator->expects(self::once())
            ->method('getViolationMessages')
            ->with($coupon, $entity)
            ->willReturn([]);

        $this->promotionProvider->expects($this->once())
            ->method('isPromotionApplied')
            ->with($entity, $promotion)
            ->willReturn(true);

        $this->promotionAwareHelper->expects($this->once())->method('isCouponAware')->willReturn(true);
        $this->assertEquals(
            ['oro.promotion.coupon.violation.coupon_promotion_already_applied'],
            $this->couponApplicabilityValidationService->getViolations($coupon, $entity)
        );
    }

    public function testGetViolationsWhenCouponPromotionNotApplicable(): void
    {
        $promotion = new Promotion();
        $coupon = (new Coupon())->setPromotion($promotion);
        $customerUser = $this->createMock(CustomerUser::class);
        $entity = (new OrderStub())->setCustomerUser($customerUser);

        $this->couponValidator->expects(self::once())
            ->method('getViolationMessages')
            ->with($coupon, $entity)
            ->willReturn([]);

        $this->promotionProvider->expects($this->once())
            ->method('isPromotionApplied')
            ->with($entity, $promotion)
            ->willReturn(false);

        $this->promotionProvider->expects($this->once())
            ->method('isPromotionApplicable')
            ->with($entity, $promotion)
            ->willReturn(false);

        $this->promotionAwareHelper->expects($this->once())->method('isCouponAware')->willReturn(true);
        $this->assertEquals(
            ['oro.promotion.coupon.violation.coupon_promotion_not_applicable'],
            $this->couponApplicabilityValidationService->getViolations($coupon, $entity)
        );
    }

    public function testGetViolationsWhenCouponIsValid(): void
    {
        $promotion = new Promotion();
        $coupon = (new Coupon())->setPromotion($promotion);
        $customerUser = $this->createMock(CustomerUser::class);
        $entity = (new OrderStub())->setCustomerUser($customerUser);

        $this->couponValidator->expects(self::once())
            ->method('getViolationMessages')
            ->with($coupon, $entity)
            ->willReturn([]);

        $this->promotionProvider->expects($this->once())
            ->method('isPromotionApplied')
            ->with($entity, $promotion)
            ->willReturn(false);

        $this->promotionProvider->expects($this->once())
            ->method('isPromotionApplicable')
            ->with($entity, $promotion)
            ->willReturn(true);

        $this->promotionAwareHelper->expects($this->once())->method('isCouponAware')->willReturn(true);
        $this->assertEmpty($this->couponApplicabilityValidationService->getViolations($coupon, $entity));
    }

    public function testGetViolationsWhenCouponIsValidWithSkipFilters(): void
    {
        $promotion = new Promotion();
        $coupon = (new Coupon())->setPromotion($promotion);
        $customerUser = $this->createMock(CustomerUser::class);
        $entity = (new OrderStub())->setCustomerUser($customerUser);

        $this->couponValidator->expects(self::once())
            ->method('getViolationMessages')
            ->with($coupon, $entity)
            ->willReturn([]);

        $this->promotionProvider->expects($this->once())
            ->method('isPromotionApplied')
            ->with($entity, $promotion)
            ->willReturn(false);

        $skipFilters = ['SomeFilterClass'];
        $this->promotionProvider->expects($this->once())
            ->method('isPromotionApplicable')
            ->with($entity, $promotion, $skipFilters)
            ->willReturn(true);

        $this->promotionAwareHelper->expects($this->once())->method('isCouponAware')->willReturn(true);
        $this->assertEmpty($this->couponApplicabilityValidationService->getViolations($coupon, $entity, $skipFilters));
    }

    public function testGetViolationsWithInvalidEntityPassed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Argument $entity should implement CustomerOwnerAwareInterface and have is_promotion_aware entity config'
        );

        $this->couponApplicabilityValidationService->getViolations(new Coupon(), new \stdClass());
    }
}
