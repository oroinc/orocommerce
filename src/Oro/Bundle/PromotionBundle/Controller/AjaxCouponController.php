<?php

namespace Oro\Bundle\PromotionBundle\Controller;

use Oro\Bundle\PromotionBundle\Entity\Repository\CouponRepository;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\PromotionBundle\Entity\Coupon;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class AjaxCouponController extends Controller
{
    /**
     * @Route("/get-added-coupons-table", name="oro_promotion_get_added_coupons_table")
     * @AclAncestor("oro_promotion_coupon_view")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAddedCouponsTableAction(Request $request)
    {
        /** @var CouponRepository $couponRepository */
        $couponRepository = $this->container
            ->get('doctrine')
            ->getManagerForClass(Coupon::class)
            ->getRepository(Coupon::class);

        $ids = $request->request->get('addedCouponIds');
        $view = $this->renderView(
            'OroPromotionBundle:Coupon:addedCouponsTable.html.twig',
            [
                'coupons' => $ids ? $couponRepository->getCouponsWithPromotionByIds(explode(',', $ids)) : [],
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
        return $this->get('oro_promotion.handler.coupon_validation_handler')->handle($request);
    }
}
