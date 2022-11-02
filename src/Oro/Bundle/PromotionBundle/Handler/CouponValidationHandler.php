<?php

namespace Oro\Bundle\PromotionBundle\Handler;

use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Exception\LogicException;
use Oro\Bundle\PromotionBundle\ValidationService\CouponApplicabilityValidationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CouponValidationHandler extends AbstractCouponHandler
{
    /**
     * @var CouponApplicabilityValidationService
     */
    private $couponApplicabilityValidationService;

    public function setCouponApplicabilityValidationService(
        CouponApplicabilityValidationService $couponApplicabilityValidationService
    ) {
        $this->couponApplicabilityValidationService = $couponApplicabilityValidationService;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request)
    {
        $coupon = $this->getCouponForValidation($request);
        $entity = $this->getActualizedEntity($request);
        $errors = $this->couponApplicabilityValidationService->getViolations($coupon, $entity);

        return new JsonResponse(['success' => empty($errors), 'errors' => $errors]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCouponForValidation(Request $request)
    {
        $couponId = $request->request->get('couponId');
        if (!$couponId) {
            throw new LogicException('Coupon id is not specified in request parameters');
        }

        $coupon = $this->getRepository(Coupon::class)->find($couponId);
        if (!$coupon) {
            throw new \RuntimeException(sprintf(
                'Cannot find "%s" entity with id "%s"',
                Coupon::class,
                $couponId
            ));
        }

        return $coupon;
    }
}
