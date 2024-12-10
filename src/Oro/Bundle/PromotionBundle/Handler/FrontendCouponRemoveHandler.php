<?php

namespace Oro\Bundle\PromotionBundle\Handler;

use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Manager\FrontendAppliedCouponManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * The handler to remove an applied coupon from an entity.
 * This handler must be used only on the storefront.
 */
class FrontendCouponRemoveHandler
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly FrontendAppliedCouponManager $frontendAppliedCouponManager
    ) {
    }

    public function handleRemove(object $entity, AppliedCoupon $appliedCoupon): void
    {
        if (!$this->authorizationChecker->isGranted('EDIT', $entity)) {
            throw new AccessDeniedException('Edit is not allowed for the requested entity.');
        }

        $isCouponRemoved = $this->frontendAppliedCouponManager->removeAppliedCoupon($entity, $appliedCoupon);
        if (!$isCouponRemoved) {
            throw new NotFoundHttpException();
        }
    }
}
