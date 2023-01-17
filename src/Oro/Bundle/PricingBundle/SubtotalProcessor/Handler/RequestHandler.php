<?php

namespace Oro\Bundle\PricingBundle\SubtotalProcessor\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Exception\EntityNotFoundException;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Handles total with subtotals calculation request.
 */
class RequestHandler
{
    private TotalProcessorProvider $totalProvider;
    private EventDispatcherInterface $eventDispatcher;
    private AuthorizationCheckerInterface $authorizationChecker;
    private EntityRoutingHelper $entityRoutingHelper;
    private ManagerRegistry $doctrine;

    public function __construct(
        TotalProcessorProvider $totalProvider,
        EventDispatcherInterface $eventDispatcher,
        AuthorizationCheckerInterface $authorizationChecker,
        EntityRoutingHelper $entityRoutingHelper,
        ManagerRegistry $doctrine
    ) {
        $this->totalProvider = $totalProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->authorizationChecker = $authorizationChecker;
        $this->entityRoutingHelper = $entityRoutingHelper;
        $this->doctrine = $doctrine;
    }

    /**
     * Calculates total with subtotals for an entity.
     */
    public function recalculateTotals(string $entityClassName, ?int $entityId, ?Request $request = null): array
    {
        $entityClassName = $this->entityRoutingHelper->resolveEntityClass($entityClassName);
        if (!class_exists($entityClassName)) {
            throw new EntityNotFoundException();
        }

        if ($entityId) {
            $entity = $this->doctrine->getRepository($entityClassName)->find($entityId);
            if (null === $entity) {
                throw new EntityNotFoundException();
            }
            if (!$this->authorizationChecker->isGranted('VIEW', $entity)) {
                throw new AccessDeniedException();
            }
        } else {
            $entity = new $entityClassName();
        }

        if ($request) {
            $event = new TotalCalculateBeforeEvent($entity, $request);
            $this->eventDispatcher->dispatch($event, TotalCalculateBeforeEvent::NAME);
            $entity = $event->getEntity();
        }

        return $this->totalProvider->enableRecalculation()->getTotalWithSubtotalsAsArray($entity);
    }
}
