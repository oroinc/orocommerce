<?php

namespace Oro\Bundle\PromotionBundle\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Model\PromotionAwareEntityHelper;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * The handler to remove applied coupon from entities to which a coupon can be applied.
 * This handler must be used only on the storefront.
 */
class FrontendCouponRemoveHandler
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private ManagerRegistry               $doctrine,
        private PromotionAwareEntityHelper    $promotionAwareHelper,
    ) {
    }

    public function handleRemove(object $entity, AppliedCoupon $appliedCoupon)
    {
        if ($this->promotionAwareHelper->isPromotionAware($entity)
            || !$this->authorizationChecker->isGranted('EDIT', $entity)
        ) {
            throw new AccessDeniedException('Edit is not allowed for requested entity');
        }

        if ($entity->getAppliedCoupons()->contains($appliedCoupon)) {
            $em = $this->doctrine->getManagerForClass(AppliedCoupon::class);
            $entity->removeAppliedCoupon($appliedCoupon);
            $em->remove($appliedCoupon);
            $em->flush();
        } else {
            throw new NotFoundHttpException();
        }
    }
}
