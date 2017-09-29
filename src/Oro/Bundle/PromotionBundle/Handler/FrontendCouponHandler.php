<?php

namespace Oro\Bundle\PromotionBundle\Handler;

use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Exception\LogicException;
use Oro\Bundle\PromotionBundle\Provider\EntityCouponsProviderInterface;
use Oro\Bundle\PromotionBundle\ValidationService\CouponApplicabilityValidationService;
use Oro\Bundle\PromotionBundle\Entity\Coupon;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FrontendCouponHandler extends AbstractCouponHandler
{
    /**
     * @var CouponApplicabilityValidationService
     */
    private $couponApplicabilityValidationService;

    /**
     * @var EntityCouponsProviderInterface
     */
    private $entityCouponsProvider;

    /**
     * @param CouponApplicabilityValidationService $couponApplicabilityValidationService
     */
    public function setCouponApplicabilityValidationService(
        CouponApplicabilityValidationService $couponApplicabilityValidationService
    ) {
        $this->couponApplicabilityValidationService = $couponApplicabilityValidationService;
    }

    /**
     * @param EntityCouponsProviderInterface $entityCouponsProvider
     */
    public function setEntityCouponsProviderService(EntityCouponsProviderInterface $entityCouponsProvider)
    {
        $this->entityCouponsProvider = $entityCouponsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request)
    {
        $coupon = $this->getCouponForValidation($request);
        if (!$coupon) {
            return new JsonResponse([
                'success' => false,
                'errors' => ['oro.promotion.coupon.violation.invalid_coupon_code']
            ]);
        }

        $entity = $this->getActualizedEntity($request);
        $errors = $this->couponApplicabilityValidationService->getViolations($coupon, $entity);

        if (empty($errors)) {
            $this->saveAppliedCoupon($coupon, $entity);
        }

        return new JsonResponse(['success' => empty($errors), 'errors' => $errors]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCouponForValidation(Request $request)
    {
        $couponCode = $request->request->get('couponCode');
        if (!$couponCode) {
            throw new LogicException('Coupon code is not specified in request parameters');
        }

        return $this->getRepository(Coupon::class)->findOneBy(['code' => $couponCode]);
    }

    /**
     * @param Coupon $coupon
     * @param AppliedCouponsAwareInterface $entity
     */
    private function saveAppliedCoupon(Coupon $coupon, AppliedCouponsAwareInterface $entity)
    {
        $appliedCoupon = $this->entityCouponsProvider->createAppliedCouponByCoupon($coupon);
        $entity->addAppliedCoupon($appliedCoupon);

        $manager = $this->registry->getManagerForClass(AppliedCoupon::class);
        $manager->persist($appliedCoupon);
        $manager->flush($appliedCoupon);
    }
}
