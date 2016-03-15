<?php

namespace OroB2B\Bundle\PricingBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Symfony\Component\HttpFoundation\JsonResponse;

class AjaxEntityTotalsController extends AbstractAjaxEntityTotalsController
{
    /**
     * @Route(
     *      "/get-totals-for-entity/{entityClassName}/{entityId}",
     *      name="orob2b_pricing_entity_totals",
     *      defaults={"entityId"=0, "entityClassName"=""}
     * )
     * @Method({"GET", "POST", "PUT"})
     * @param string $entityClassName
     * @param integer $entityId
     *
     * @return JsonResponse
     */
    public function entityTotalsAction($entityClassName, $entityId)
    {
        $totals = $this->getTotals($entityClassName, $entityId);

        return new JsonResponse(
            $totals
        );
    }
}
