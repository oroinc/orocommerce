<?php

namespace Oro\Bundle\PricingBundle\Controller;

use Oro\Bundle\EntityBundle\Exception\EntityNotFoundException;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Handler\RequestHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Ajax Entity Totals Controller
 */
class AjaxEntityTotalsController extends AbstractController
{
    /**
     * @Route(
     *      "/get-totals-for-entity/{entityClassName}/{entityId}",
     *      name="oro_pricing_entity_totals",
     *      requirements={"entityId"="\d+"},
     *      defaults={"entityId"=0, "entityClassName"=""}
     * )
     *
     * @param string $entityClassName
     * @param integer $entityId
     *
     * @return JsonResponse
     */
    public function getEntityTotalsAction($entityClassName, $entityId)
    {
        try {
            $totalRequestHandler = $this->get(RequestHandler::class);
            $totals = $totalRequestHandler->recalculateTotals($entityClassName, $entityId);
        } catch (EntityNotFoundException $e) {
            return new JsonResponse('', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(
            $totals
        );
    }

    /**
     * @Route(
     *      "/recalculate-totals-for-entity/{entityClassName}/{entityId}",
     *      name="oro_pricing_recalculate_entity_totals",
     *      defaults={"entityId"=0, "entityClassName"=""},
     *      methods={"POST"}
     * )
     *
     * @param Request $request
     * @param string $entityClassName
     * @param integer $entityId
     *
     * @return JsonResponse
     */
    public function recalculateTotalsAction(Request $request, $entityClassName, $entityId)
    {
        try {
            $totalRequestHandler = $this->get(RequestHandler::class);
            $totals = $totalRequestHandler->recalculateTotals($entityClassName, $entityId, $request);
        } catch (EntityNotFoundException $e) {
            return new JsonResponse('', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(
            $totals
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                RequestHandler::class,
            ]
        );
    }
}
