<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\EntityBundle\Exception\EntityNotFoundException;

class AjaxEntityTotalsController extends Controller
{
    /**
     * @Route(
     *      "/get-totals-for-entity/{entityClassName}/{entityId}",
     *      name="oro_shopping_list_pricing_frontend_entity_totals",
     *      requirements={"entityId"="\d+"},
     *      defaults={"entityId"=0, "entityClassName"=""}
     * )
     *
     * @param integer $entityId
     *
     * @return JsonResponse
     */
    public function getEntityTotalsAction($entityClassName, $entityId)
    {
        try {
            $totalRequestHandler = $this->get('oro_shopping_list.pricing.subtotal_processor.handler.request_handler');
            $totals = $totalRequestHandler->recalculateTotals($entityClassName, $entityId);
        } catch (EntityNotFoundException $e) {
            return new JsonResponse('', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($totals);
    }
}
