<?php

namespace Oro\Bundle\PromotionBundle\Controller\Frontend;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Handler\FrontendCouponHandler;
use Oro\Bundle\PromotionBundle\Handler\FrontendCouponRemoveHandler;
use Oro\Bundle\PromotionBundle\Model\PromotionAwareEntityHelper;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Ajax Coupon Controller
 */
#[CsrfProtection()]
class AjaxCouponController extends AbstractController
{
    /**
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[Route(path: '/add-coupon', name: 'oro_promotion_frontend_add_coupon', methods: ['POST'])]
    public function addCouponAction(Request $request)
    {
        return $this->container->get(FrontendCouponHandler::class)->handle($request);
    }

    /**
     *
     * @param string $entityClass
     * @param int $entityId
     * @param AppliedCoupon $appliedCoupon
     * @return JsonResponse
     */
    #[Route(
        path: '/{entityClass}/{entityId}/remove-coupon/{id}',
        name: 'oro_promotion_frontend_remove_coupon',
        requirements: ['entityId' => '\d+', 'id' => '\d+'],
        methods: ['DELETE']
    )]
    public function removeCouponAction($entityClass, $entityId, AppliedCoupon $appliedCoupon)
    {
        $entity = $this->container->get(EntityRoutingHelper::class)->getEntity($entityClass, $entityId);
        /** @var PromotionAwareEntityHelper $promotionAwareHelper */
        $promotionAwareHelper = $this->container->get(PromotionAwareEntityHelper::class);
        if (!$promotionAwareHelper->isCouponAware($entity)) {
            throw new BadRequestHttpException('Unsupported entity class ' . ClassUtils::getClass($entity));
        }

        $this->container->get(FrontendCouponRemoveHandler::class)->handleRemove($entity, $appliedCoupon);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                PromotionAwareEntityHelper::class,
                FrontendCouponHandler::class,
                EntityRoutingHelper::class,
                FrontendCouponRemoveHandler::class,
            ]
        );
    }
}
