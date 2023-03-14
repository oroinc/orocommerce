<?php

namespace Oro\Bundle\PromotionBundle\Layout\Extension;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\PromotionBundle\Model\PromotionAwareEntityHelper;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

/**
 * Set isAppliedCouponsAware to context if entity or source entity of checkout
 * is coupons aware
 */
class AppliedCouponsAwareContextConfigurator implements ContextConfiguratorInterface
{
    public function __construct(protected PromotionAwareEntityHelper $promotionAwareHelper)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $isAppliedCouponsAware = false;
        if ($context->data()->has('checkout')) {
            $checkout = $context->data()->get('checkout');
            $isAppliedCouponsAware = $checkout instanceof CheckoutInterface
                && $this->promotionAwareHelper->isCouponAware($checkout)
                && !$checkout->getSourceEntity() instanceof QuoteDemand;
        } elseif ($context->data()->has('entity')) {
            $entity = $context->data()->get('entity');
            $isAppliedCouponsAware = is_object($entity) && $this->promotionAwareHelper->isCouponAware($entity);
        }

        $context->getResolver()->setDefault('isAppliedCouponsAware', false);
        $context->set('isAppliedCouponsAware', $isAppliedCouponsAware);
    }
}
