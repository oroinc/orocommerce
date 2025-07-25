<?php

namespace Oro\Bundle\PromotionBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Repository\CouponRepository;
use Oro\Bundle\PromotionBundle\Handler\CouponValidationHandler;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Serves coupon actions.
 */
class AjaxCouponController extends AbstractController
{
    /**
     *
     * @param string $addedCouponIds
     * @return JsonResponse
     */
    #[Route(
        path: '/get-added-coupons-table/{addedCouponIds}',
        name: 'oro_promotion_get_added_coupons_table',
        defaults: ['addedCouponIds' => 0]
    )]
    #[AclAncestor('oro_promotion_coupon_view')]
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
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[Route(path: '/validate-coupon-applicability', name: 'oro_promotion_validate_coupon_applicability')]
    #[AclAncestor('oro_promotion_coupon_view')]
    public function validateCouponApplicabilityAction(Request $request)
    {
        return $this->container->get(CouponValidationHandler::class)->handle($request);
    }

    /**
     *
     * @param string $couponIds
     * @return JsonResponse
     */
    #[Route(
        path: '/get-applied-coupons-data/{couponIds}',
        name: 'oro_promotion_get_applied_coupons_data',
        defaults: ['couponIds' => 0]
    )]
    #[AclAncestor('oro_promotion_coupon_view')]
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
        return $this->container->get(ManagerRegistry::class)
            ->getManagerForClass(Coupon::class)
            ->getRepository(Coupon::class);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                CouponValidationHandler::class,
                ManagerRegistry::class,
            ]
        );
    }
}
