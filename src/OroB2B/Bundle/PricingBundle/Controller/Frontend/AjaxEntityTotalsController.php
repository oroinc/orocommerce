<?php

namespace OroB2B\Bundle\PricingBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class AjaxEntityTotalsController extends Controller
{
    /**
     * @Route(
     *      "/get-totals-for-entity/{entityId}/{entityClassName}",
     *      name="orob2b_pricing_frontend_entity_totals",
     *      requirements={"entityId"="\d+"},
     *      defaults={"entityId"=0, "entityClassName"=""}
     * )
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getEntityTotalsAction($entityId, $entityClassName)
    {
        $entityClass = $this->get('oro_entity.routing_helper')->resolveEntityClass($entityClassName);

        if (!class_exists($entityClass)) {
            throw $this->createNotFoundException();
        }

        /** @var OroEntityManager $entityManager */
        $entityManager = $this->getDoctrine()->getManager();
        $entity = $entityManager->getRepository($entityClass)->find($entityId);

        if (!$entity) {
            throw $this->createNotFoundException();
        }
        /** @var SecurityFacade $securityFacade */
        $securityFacade = $this->get('oro_security.security_facade');
        $isGranted      = $securityFacade->isGranted('VIEW', $entity);
        if (!$isGranted) {
            throw new AccessDeniedException();
        }

        $totalProvider = $this->get('orob2b_pricing.subtotal_processor.total_processor_provider');
        $total  = $totalProvider->getTotal($entity)->toArray();
        $subtotals = $totalProvider->getSubtotals($entity)->getValues();
        //if there is only one subtotal, it will be the same with total, so it should be ignored
        $subtotals = count($subtotals) === 1 ? [] : $subtotals;

        $callbackFunction = function ($value) {
            return $value->toArray();
        };

        return new JsonResponse(
            [
                'total' => $total,
                'subtotals' => array_map($callbackFunction, $subtotals)
            ]
        );
    }
}
