<?php

namespace OroB2B\Bundle\PricingBundle\SubtotalProcessor\Handler;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;

use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\EntityBundle\Exception\EntityNotFoundException;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;

use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class RequestHandler
{
    /** @var TotalProcessorProvider */
    protected $totalProvider;

    /** @var ContainerAwareEventDispatcher */
    protected $eventDispatcher;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var RequestStack */
    protected $requestStack;

    /** @var  EntityRoutingHelper */
    protected $entityRoutingHelper;

    /** @var  Registry */
    protected $doctrine;

    /**
     * @param TotalProcessorProvider $totalProvider
     * @param ContainerAwareEventDispatcher $eventDispatcher
     * @param SecurityFacade $securityFacade
     * @param RequestStack $requestStack
     * @param EntityRoutingHelper $entityRoutingHelper
     * @param Registry $doctrine
     */
    public function __construct(
        TotalProcessorProvider $totalProvider,
        ContainerAwareEventDispatcher $eventDispatcher,
        SecurityFacade $securityFacade,
        RequestStack $requestStack,
        EntityRoutingHelper $entityRoutingHelper,
        Registry $doctrine
    ) {
        $this->totalProvider = $totalProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->securityFacade = $securityFacade;
        $this->requestStack = $requestStack;
        $this->entityRoutingHelper = $entityRoutingHelper;
        $this->doctrine = $doctrine;
    }

    /**
     * @param string $entityClassName
     * @param integer $entityId
     *
     * @return array
     */
    public function getTotals($entityClassName, $entityId)
    {
        $this->existClassName($entityClassName);

        $entity = $this->getExistEntity($entityClassName, $entityId);
        $this->hasAccessView($entity);

        $total = $this->totalProvider->getTotal($entity)->toArray();
        $subtotals = $this->totalProvider->getSubtotals($entity)->getValues();

        $totals = $this->prepareResponse($total, $subtotals);

        return $totals;
    }

    /**
     * @param $entityClassName
     * @param $entityId
     *
     * @return array
     */
    public function recalculateTotals($entityClassName, $entityId)
    {
        $this->existClassName($entityClassName);

        if ($entityId) {
            $entity = $this->getExistEntity($entityClassName, $entityId);
            $this->hasAccessEdit($entity);
        } else {
            $entity = new $entityClassName();
            $this->hasAccessCreate($entity);
        }

        $event = $this->dispatchPreCalculateTotalEvent($entity);
        $entity = $event->getEntity();

        $total = $this->totalProvider->getTotal($entity)->toArray();
        $subtotals = $this->totalProvider->getSubtotals($entity)->getValues();
        $totals = $this->prepareResponse($total, $subtotals);

        return $totals;
    }

    /**
     * @param $total
     * @param $subtotals
     *
     * @return array
     */
    protected function prepareResponse($total, $subtotals)
    {
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

    /**
     * @param $entity
     *
     * @return TotalCalculateBeforeEvent
     */
    protected function dispatchPreCalculateTotalEvent($entity)
    {
        $event = new TotalCalculateBeforeEvent($entity, $this->requestStack->getCurrentRequest());

        $event = $this->eventDispatcher->dispatch(TotalCalculateBeforeEvent::NAME, $event);

        return $event;
    }

    /**
     * @param $entityClass
     * @param $entityId
     *
     * @return object
     * @throws EntityNotFoundException
     */
    protected function getExistEntity($entityClass, $entityId)
    {
        $entityManager = $this->doctrine->getManager();
        $entity = $entityManager->getRepository($entityClass)->find($entityId);

        if (!$entity) {
            throw new EntityNotFoundException();
        }

        return $entity;
    }

    /**
     * @param $entity
     */
    protected function hasAccessView($entity)
    {
        $isGranted = $this->securityFacade->isGranted('VIEW', $entity);
        if (!$isGranted) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @param $entity
     */
    protected function hasAccessEdit($entity)
    {
        $isGranted = $this->securityFacade->isGranted('EDIT', $entity);
        if (!$isGranted) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @param $entity
     */
    protected function hasAccessCreate($entity)
    {
        $isGranted = $this->securityFacade->isGranted('CREATE', $entity);
        if (!$isGranted) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @param $entityClassName
     *
     * @throws EntityNotFoundException
     */
    protected function existClassName($entityClassName)
    {
        $entityClass = $this->entityRoutingHelper->resolveEntityClass($entityClassName);

        if (!class_exists($entityClass)) {
            throw new EntityNotFoundException();
        }
    }
}
