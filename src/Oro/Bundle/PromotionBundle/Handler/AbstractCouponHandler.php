<?php

namespace Oro\Bundle\PromotionBundle\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\EntityBundle\Exception\EntityNotFoundException;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Exception\LogicException;
use Oro\Bundle\PromotionBundle\Model\PromotionAwareEntityHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Base handle coupon applicability and apply it by code.
 */
abstract class AbstractCouponHandler
{
    public function __construct(
        protected EntityRoutingHelper           $routingHelper,
        protected ManagerRegistry               $registry,
        protected EventDispatcherInterface      $eventDispatcher,
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected PromotionAwareEntityHelper    $promotionAwareHelper
    ) {
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
     * @return object
     * @throws LogicException|AccessDeniedException
     */
    protected function getActualizedEntity(Request $request)
    {
        $entityClass = $request->request->get('entityClass');
        if (!$entityClass) {
            throw new LogicException('Entity class is not specified in request parameters');
        }
        $resolvedEntityClass = $this->resolveEntityClass($entityClass);

        $entityId = (int)$request->request->get('entityId');
        if ($entityId) {
            $entity = $this->getRepository($resolvedEntityClass)->find($entityId);
        } else {
            $entity = new $resolvedEntityClass();
        }

        if (!$this->promotionAwareHelper->isCouponAware($entityClass)) {
            throw new LogicException('Entity should have is_coupon_aware entity config');
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
