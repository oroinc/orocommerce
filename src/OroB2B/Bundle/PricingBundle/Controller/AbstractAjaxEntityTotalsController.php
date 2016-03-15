<?php

namespace OroB2B\Bundle\PricingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;

abstract class AbstractAjaxEntityTotalsController extends Controller
{
    /**
     * @param string $entityClassName
     * @param integer $entityId
     * @return array
     */
    protected function getTotals($entityClassName, $entityId)
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
        $isGranted = $securityFacade->isGranted('VIEW', $entity);
        if (!$isGranted) {
            throw new AccessDeniedException();
        }

        $totalProvider = $this->get('orob2b_pricing.subtotal_processor.total_processor_provider');
        $total = $totalProvider->getTotal($entity)->toArray();
        $subtotals = $totalProvider->getSubtotals($entity)->getValues();

        $callbackFunction = function ($value) {
            /** @var Subtotal $value */
            return $value->toArray();
        };
        $totals = [
            'total' => $total,
            'subtotals' => array_map($callbackFunction, $subtotals)
        ];

        return $totals;
    }
}
