<?php

namespace Oro\Bundle\PromotionBundle\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Exception\EntityNotFoundException;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Oro\Bundle\PromotionBundle\Manager\FrontendAppliedCouponManager;
use Oro\Bundle\PromotionBundle\ValidationService\CouponApplicabilityValidationService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * The handler to apply a coupon to an entity.
 * This handler must be used only on the storefront.
 */
class FrontendCouponHandler
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly EntityRoutingHelper $routingHelper,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly FrontendAppliedCouponManager $frontendAppliedCouponManager
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        $couponCode = $request->request->get('couponCode');
        if (!$couponCode) {
            throw new \LogicException('The coupon code is not specified in request parameters.');
        }

        $entity = $this->getEntity($request);
        if (null === $entity) {
            return new JsonResponse([
                'success' => false,
                'errors' => [CouponApplicabilityValidationService::MESSAGE_PROMOTION_NOT_APPLICABLE]
            ]);
        }
        if (!$this->authorizationChecker->isGranted('EDIT', $entity)) {
            throw new AccessDeniedException('Edit is not allowed for the requested entity.');
        }

        $this->eventDispatcher->dispatch(
            new TotalCalculateBeforeEvent($entity, $request),
            TotalCalculateBeforeEvent::NAME
        );

        $errors = new ArrayCollection();
        $isCouponApplied = $this->frontendAppliedCouponManager->applyCoupon($entity, $couponCode, $errors);

        return new JsonResponse(['success' => $isCouponApplied, 'errors' => $errors->toArray()]);
    }

    private function getEntity(Request $request): ?object
    {
        $entityClass = $this->getEntityClass($request);
        $entityId = (int)$request->request->get('entityId');

        return $entityId
            ? $this->doctrine->getManagerForClass($entityClass)->find($entityClass, $entityId)
            : null;
    }

    private function getEntityClass(Request $request): string
    {
        $entityClass = $request->request->get('entityClass');
        if (!$entityClass) {
            throw new \LogicException('The entity class is not specified in request parameters.');
        }

        $resolvedEntityClass = $this->routingHelper->resolveEntityClass($entityClass);
        if (!class_exists($resolvedEntityClass)) {
            throw new EntityNotFoundException(\sprintf('Cannot resolve entity class "%s".', $entityClass));
        }

        return $resolvedEntityClass;
    }
}
