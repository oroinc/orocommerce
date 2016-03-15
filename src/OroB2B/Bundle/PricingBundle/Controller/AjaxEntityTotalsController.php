<?php

namespace OroB2B\Bundle\PricingBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\JsonResponse;

class AjaxEntityTotalsController extends AbstractAjaxEntityTotalsController
{
    /**
     * @Route(
     *      "/get-totals-for-entity/{entityClassName}/{entityId}",
     *      name="orob2b_pricing_entity_totals",
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
