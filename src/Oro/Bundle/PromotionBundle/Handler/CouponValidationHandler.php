<?php

namespace Oro\Bundle\PromotionBundle\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\EntityBundle\Exception\EntityNotFoundException;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Oro\Bundle\PromotionBundle\Exception\LogicException;
use Oro\Bundle\PromotionBundle\ValidationService\CouponApplicabilityValidationService;
use Oro\Bundle\PromotionBundle\Entity\Coupon;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CouponValidationHandler
{
    /**
     * @var CouponApplicabilityValidationService
     */
    private $couponApplicabilityValidationService;

    /**
     * @var EntityRoutingHelper
     */
    private $routingHelper;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param CouponApplicabilityValidationService $couponApplicabilityValidationService
     * @param EntityRoutingHelper $routingHelper
     * @param ManagerRegistry $registry
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        CouponApplicabilityValidationService $couponApplicabilityValidationService,
        EntityRoutingHelper $routingHelper,
        ManagerRegistry $registry,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->couponApplicabilityValidationService = $couponApplicabilityValidationService;
        $this->routingHelper = $routingHelper;
        $this->registry = $registry;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request)
    {
        $coupon = $this->getCouponForValidation($request);
        $entity = $this->getActualizedEntity($request);
        $errors = $this->couponApplicabilityValidationService->getViolations($coupon, $entity);

        return new JsonResponse(['success' => empty($errors), 'errors' => $errors]);
    }

    /**
     * @param Request $request
     * @return Coupon
     * @throws LogicException
     */
    private function getCouponForValidation(Request $request)
    {
        $couponId = $request->request->get('couponId');
        if (!$couponId) {
            throw new LogicException('Coupon id is not specified in request parameters');
        }

        return $this->getRepository(Coupon::class)->find($couponId);
    }

    /**
     * @param Request $request
     * @return object
     * @throws LogicException
     */
    private function getActualizedEntity(Request $request)
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

        $event = new TotalCalculateBeforeEvent($entity, $request);
        $this->eventDispatcher->dispatch(TotalCalculateBeforeEvent::NAME, $event);

        return $event->getEntity();
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

    /**
     * @param string $className
     * @return ObjectRepository
     */
    private function getRepository($className)
    {
        return $this->registry
            ->getManagerForClass($className)
            ->getRepository($className);
    }
}
