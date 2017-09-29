<?php

namespace Oro\Bundle\PromotionBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class AjaxCouponController extends Controller
{
    /**
     * @Route("/add-coupon", name="oro_promotion_frontend_add_coupon")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addCouponAction(Request $request)
    {
        return $this->get('oro_promotion.handler.frontend_coupon_handler')->handle($request);
    }
}
