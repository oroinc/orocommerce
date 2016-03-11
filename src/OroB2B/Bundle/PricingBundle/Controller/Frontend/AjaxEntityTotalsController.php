<?php

namespace OroB2B\Bundle\PricingBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\JsonResponse;

use OroB2B\Bundle\PricingBundle\Controller\AbstractAjaxEntityTotalsController;

class AjaxEntityTotalsController extends AbstractAjaxEntityTotalsController
{
    /**
     * @Route(
     *      "/get-totals-for-entity/{entityClassName}/{entityId}",
     *      name="orob2b_pricing_frontend_entity_totals",
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
        $totals = $this->getTotals($entityClassName, $entityId);

        return new JsonResponse(
            $totals
        );
    }
}
