<?php

namespace Oro\Bundle\PromotionBundle\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotionsAwareInterface;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class FrontendCouponRemoveHandler
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ManagerRegistry $registry
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ManagerRegistry $registry
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->registry = $registry;
    }

    /**
     * @param AppliedCouponsAwareInterface $entity
     * @param AppliedCoupon $appliedCoupon
     */
    public function handleRemove(AppliedCouponsAwareInterface $entity, AppliedCoupon $appliedCoupon)
    {
        if ($entity instanceof AppliedPromotionsAwareInterface
            || !$this->authorizationChecker->isGranted('EDIT', $entity)
        ) {
            throw new ForbiddenException('Edit is not allowed for requested entity');
        }

        if ($entity->getAppliedCoupons()->contains($appliedCoupon)) {
            $em = $this->registry->getManagerForClass(AppliedCoupon::class);
            $entity->removeAppliedCoupon($appliedCoupon);
            $em->remove($appliedCoupon);
            $em->flush();
        } else {
            throw new NotFoundHttpException();
        }
    }
}
