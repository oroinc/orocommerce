<?php

namespace Oro\Bundle\PromotionBundle\Controller;

use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Repository\CouponRepository;
use Oro\Bundle\PromotionBundle\Handler\CouponValidationHandler;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Serves coupon actions.
 */
class AjaxCouponController extends AbstractController
{
    /**
     * @Route(
     *     "/get-added-coupons-table/{addedCouponIds}",
     *     name="oro_promotion_get_added_coupons_table",
     *     defaults={"addedCouponIds"="0"}
     * )
     * @AclAncestor("oro_promotion_coupon_view")
     *
     * @param string $addedCouponIds
     * @return JsonResponse
     */
    public function getAddedCouponsTableAction($addedCouponIds)
    {
        $coupons = $this->getCouponRepository()->getCouponsWithPromotionByIds(explode(',', $addedCouponIds));
        $view = $this->renderView(
            '@OroPromotion/Coupon/addedCouponsTable.html.twig',
            [
                'coupons' => $coupons,
            ]
        );

        return new JsonResponse($view);
    }

    /**
     * @Route("/validate-coupon-applicability", name="oro_promotion_validate_coupon_applicability")
     * @AclAncestor("oro_promotion_coupon_view")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateCouponApplicabilityAction(Request $request)
    {
        return $this->get(CouponValidationHandler::class)->handle($request);
    }

    /**
     * @Route(
     *     "/get-applied-coupons-data/{couponIds}",
     *     name="oro_promotion_get_applied_coupons_data",
     *     defaults={"couponIds"="0"}
     * )
     * @AclAncestor("oro_promotion_coupon_view")
     *
     * @param string $couponIds
     * @return JsonResponse
     */
    public function getAppliedCouponsData($couponIds)
    {
        $data = [];
        $coupons = $this->getCouponRepository()->getCouponsWithPromotionByIds(explode(',', $couponIds));
        foreach ($coupons as $coupon) {
            $data[] = [
                'couponCode' => $coupon->getCode(),
                'sourcePromotionId' => $coupon->getPromotion()->getId(),
                'sourceCouponId' => $coupon->getId(),
            ];
        }

        return new JsonResponse($data);
    }

    /**
     * @return CouponRepository
     */
    private function getCouponRepository()
    {
        return $this->get('doctrine')
            ->getManagerForClass(Coupon::class)
            ->getRepository(Coupon::class);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                CouponValidationHandler::class,
            ]
        );
    }
}
