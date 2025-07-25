<?php

namespace Oro\Bundle\PromotionBundle\Controller;

use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Mapper\AppliedPromotionMapper;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Serves promotion actions.
 */
class AjaxPromotionController extends AbstractController
{
    #[Route(
        path: '/get-promotion-details/{id}',
        name: 'oro_promotion_get_promotion_by_promotion',
        requirements: ['id' => '\d+']
    )]
    #[AclAncestor('oro_promotion_view')]
    public function getPromotionDataByPromotionAction(Promotion $promotion): JsonResponse
    {
        return $this->getPromotionJsonResponse($promotion);
    }

    #[Route(
        path: '/get-applied-promotion-details/{id}',
        name: 'oro_promotion_get_promotion_by_applied_promotion',
        requirements: ['id' => '\d+']
    )]
    #[AclAncestor('oro_promotion_view')]
    public function getPromotionDataByAppliedPromotionAction(AppliedPromotion $appliedPromotion): JsonResponse
    {
        $mapper = $this->container->get(AppliedPromotionMapper::class);

        return $this->getPromotionJsonResponse($mapper->mapAppliedPromotionToPromotionData($appliedPromotion));
    }

    private function getPromotionJsonResponse(PromotionDataInterface $promotionData): JsonResponse
    {
        $view = $this->renderView(
            '@OroPromotion/Promotion/getPromotionDetails.html.twig',
            [
                'entity' => $promotionData,
                'scopeEntities' => $this->container->get(ScopeManager::class)->getScopeEntities('promotion')
            ]
        );

        return new JsonResponse($view);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                AppliedPromotionMapper::class,
                ScopeManager::class,
            ]
        );
    }
}
