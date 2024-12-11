<?php

namespace Oro\Bundle\PromotionBundle\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Exception\EntityNotFoundException;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Model\PromotionAwareEntityHelper;
use Oro\Bundle\PromotionBundle\ValidationService\CouponApplicabilityValidationService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * The handler to validate coupons applied to an entity.
 */
class CouponValidationHandler
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly EntityRoutingHelper $routingHelper,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly PromotionAwareEntityHelper $promotionAwareHelper,
        private readonly CouponApplicabilityValidationService $couponApplicabilityValidationService
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        $couponId = $request->request->get('couponId');
        if (!$couponId) {
            throw new \LogicException('The coupon ID is not specified in request parameters.');
        }

        $entity = $this->getEntity($request);
        if (null === $entity) {
            return new JsonResponse([
                'success' => false,
                'errors' => [CouponApplicabilityValidationService::MESSAGE_PROMOTION_NOT_APPLICABLE]
            ]);
        }
        if (!$this->authorizationChecker->isGranted('EDIT', $entity)) {
            throw new AccessDeniedException();
        }

        $coupon = $this->doctrine->getManagerForClass(Coupon::class)->find(Coupon::class, $couponId);
        if (!$coupon) {
            throw new \RuntimeException(\sprintf('Cannot find "%s" entity with ID "%s".', Coupon::class, $couponId));
        }

        $this->eventDispatcher->dispatch(
            new TotalCalculateBeforeEvent($entity, $request),
            TotalCalculateBeforeEvent::NAME
        );

        $errors = $this->couponApplicabilityValidationService->getViolations($coupon, $entity);

        return new JsonResponse(['success' => empty($errors), 'errors' => $errors]);
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

        if (!$this->promotionAwareHelper->isCouponAware($entityClass)) {
            throw new \LogicException('The entity must be coupon aware.');
        }

        return $resolvedEntityClass;
    }
}
