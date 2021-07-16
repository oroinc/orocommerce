<?php

namespace Oro\Bundle\PromotionBundle\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\EntityBundle\Exception\EntityNotFoundException;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Exception\LogicException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

abstract class AbstractCouponHandler
{
    /**
     * @var EntityRoutingHelper
     */
    protected $routingHelper;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    public function __construct(
        EntityRoutingHelper $routingHelper,
        ManagerRegistry $registry,
        EventDispatcherInterface $eventDispatcher,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->routingHelper = $routingHelper;
        $this->registry = $registry;
        $this->eventDispatcher = $eventDispatcher;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    abstract public function handle(Request $request);

    /**
     * @param Request $request
     * @return Coupon|null
     * @throws LogicException
     */
    abstract protected function getCouponForValidation(Request $request);

    /**
     * @param Request $request
     * @return AppliedCouponsAwareInterface
     * @throws LogicException|AccessDeniedException
     */
    protected function getActualizedEntity(Request $request)
    {
        $entityClass = $request->request->get('entityClass');
        if (!$entityClass) {
            throw new LogicException('Entity class is not specified in request parameters');
        }
        $resolvedEntityClass = $this->resolveEntityClass($entityClass);

        $entityId = (int) $request->request->get('entityId');
        if ($entityId) {
            $entity = $this->getRepository($resolvedEntityClass)->find($entityId);
        } else {
            $entity = new $resolvedEntityClass();
        }

        if (!$entity instanceof AppliedCouponsAwareInterface) {
            throw new LogicException('Entity should be instance of AppliedCouponsAwareInterface');
        }

        if (!$this->authorizationChecker->isGranted('EDIT', $entity)) {
            throw new AccessDeniedException();
        }

        $event = new TotalCalculateBeforeEvent($entity, $request);
        $this->eventDispatcher->dispatch($event, TotalCalculateBeforeEvent::NAME);

        return $entity;
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    protected function getRepository($className)
    {
        return $this->registry
            ->getManagerForClass($className)
            ->getRepository($className);
    }

    /**
     * @param string $entityClass
     * @return string
     * @throws EntityNotFoundException
     */
    private function resolveEntityClass($entityClass)
    {
        $resolvedEntityClass = $this->routingHelper->resolveEntityClass($entityClass);

        if (!class_exists($resolvedEntityClass)) {
            throw new EntityNotFoundException(sprintf('Cannot resolve entity class "%s"', $entityClass));
        }

        return $resolvedEntityClass;
    }
}
