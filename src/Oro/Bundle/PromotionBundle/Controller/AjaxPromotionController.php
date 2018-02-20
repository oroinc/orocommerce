<?php

namespace Oro\Bundle\PromotionBundle\Controller;

use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class AjaxPromotionController extends Controller
{
    /**
     * @Route(
     *     "/get-promotion-details/{id}",
     *      name="oro_promotion_get_promotion_by_promotion",
     *      requirements={"id"="\d+"}
     * )
     * @AclAncestor("oro_promotion_view")
     *
     * @param Promotion $promotion
     * @return JsonResponse
     */
    public function getPromotionDataByPromotionAction(Promotion $promotion): JsonResponse
    {
        return $this->getPromotionJsonResponse($promotion);
    }

    /**
     * @Route(
     *     "/get-applied-promotion-details/{id}",
     *      name="oro_promotion_get_promotion_by_applied_promotion",
     *      requirements={"id"="\d+"}
     * )
     * @AclAncestor("oro_promotion_view")
     *
     * @param AppliedPromotion $appliedPromotion
     * @return JsonResponse
     */
    public function getPromotionDataByAppliedPromotionAction(AppliedPromotion $appliedPromotion): JsonResponse
    {
        $mapper = $this->get('oro_promotion.mapper.applied_promotion');

        return $this->getPromotionJsonResponse($mapper->mapAppliedPromotionToPromotionData($appliedPromotion));
    }

    /**
     * @param PromotionDataInterface $promotionData
     * @return JsonResponse
     */
    private function getPromotionJsonResponse(PromotionDataInterface $promotionData): JsonResponse
    {
        $view = $this->renderView(
            'OroPromotionBundle:Promotion:getPromotionDetails.html.twig',
            [
                'entity' => $promotionData,
                'scopeEntities' => $this->get('oro_scope.scope_manager')->getScopeEntities('promotion')
            ]
        );

        return new JsonResponse($view);
    }
}
