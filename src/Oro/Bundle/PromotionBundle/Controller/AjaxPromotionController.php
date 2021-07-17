<?php

namespace Oro\Bundle\PromotionBundle\Controller;

use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class AjaxPromotionController extends AbstractController
{
    /**
     * @Route(
     *     "/get-promotion-details/{id}",
     *      name="oro_promotion_get_promotion_by_promotion",
     *      requirements={"id"="\d+"}
     * )
     * @AclAncestor("oro_promotion_view")
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
     */
    public function getPromotionDataByAppliedPromotionAction(AppliedPromotion $appliedPromotion): JsonResponse
    {
        $mapper = $this->get('oro_promotion.mapper.applied_promotion');

        return $this->getPromotionJsonResponse($mapper->mapAppliedPromotionToPromotionData($appliedPromotion));
    }

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
