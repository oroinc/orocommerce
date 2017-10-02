<?php

namespace Oro\Bundle\PromotionBundle\Controller\Frontend;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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

    /**
     * @Route(
     *     "/{entityClass}/{entityId}/remove-coupon/{id}",
     *     name="oro_promotion_frontend_remove_coupon",
     *     requirements={
     *          "entityId"="\d+",
     *          "id"="\d+"
     *     }
     * )
     *
     * @param string $entityClass
     * @param int $entityId
     * @param AppliedCoupon $appliedCoupon
     * @return JsonResponse
     */
    public function removeCouponAction($entityClass, $entityId, AppliedCoupon $appliedCoupon)
    {
        $entity = $this->get('oro_entity.routing_helper')->getEntity($entityClass, $entityId);
        if (!$entity instanceof AppliedCouponsAwareInterface) {
            throw new BadRequestHttpException('Unsupported entity class ' . ClassUtils::getClass($entity));
        }

        $this->get('oro_promotion.handler.frontend_coupon_remove_handler')->handleRemove($entity, $appliedCoupon);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
