<?php

namespace Oro\Bundle\PricingBundle\SubtotalProcessor\Handler;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\EntityBundle\Exception\EntityNotFoundException;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class RequestHandler
{
    /** @var TotalProcessorProvider */
    protected $totalProvider;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var  EntityRoutingHelper */
    protected $entityRoutingHelper;

    /** @var  Registry */
    protected $doctrine;

    /**
     * @param TotalProcessorProvider $totalProvider
     * @param EventDispatcherInterface $eventDispatcher
     * @param SecurityFacade $securityFacade
     * @param EntityRoutingHelper $entityRoutingHelper
     * @param Registry $doctrine
     */
    public function __construct(
        TotalProcessorProvider $totalProvider,
        EventDispatcherInterface $eventDispatcher,
        SecurityFacade $securityFacade,
        EntityRoutingHelper $entityRoutingHelper,
        Registry $doctrine
    ) {
        $this->totalProvider = $totalProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->securityFacade = $securityFacade;
        $this->entityRoutingHelper = $entityRoutingHelper;
        $this->doctrine = $doctrine;
    }

    /**
     * Calculate total with subtotals for entity
     *
     * @param string $entityClassName
     * @param int|null $entityId
     * @param Request|null $request - can be used data from request for dynamic recalculate for form data
     *
     * @return array
     */
    public function recalculateTotals($entityClassName, $entityId, $request = null)
    {
        $entityClassName = $this->resolveClassName($entityClassName);

        if ($entityId) {
            $entity = $this->getExistEntity($entityClassName, $entityId);
            $this->hasAccessView($entity);
        } else {
            $entity = new $entityClassName();
        }

        if ($request) {
            $event = $this->dispatchPreCalculateTotalEvent($entity, $request);
            $entity = $event->getEntity();
        }

        $this->totalProvider->enableRecalculation();

        return $this->totalProvider->getTotalWithSubtotalsAsArray($entity);
    }

    /**
     * Dispatch event TotalCalculateBeforeEvent to fill entity
     *
     * @param object $entity
     * @param Request $request
     *
     * @return TotalCalculateBeforeEvent
     */
    protected function dispatchPreCalculateTotalEvent($entity, $request)
    {
        $event = new TotalCalculateBeforeEvent($entity, $request);
        $event = $this->eventDispatcher->dispatch(TotalCalculateBeforeEvent::NAME, $event);

        return $event;
    }

    /**
     * @param string  $entityClass
     * @param int $entityId
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
     * @param object $entity
     */
    protected function hasAccessView($entity)
    {
        $isGranted = $this->securityFacade->isGranted('VIEW', $entity);
        if (!$isGranted) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @param string $entityClassName
     * @return string
     *
     * @throws EntityNotFoundException
     */
    protected function resolveClassName($entityClassName)
    {
        $entityClass = $this->entityRoutingHelper->resolveEntityClass($entityClassName);

        if (!class_exists($entityClass)) {
            throw new EntityNotFoundException();
        }

        return $entityClass;
    }
}
