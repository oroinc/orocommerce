<?php

namespace Oro\Bundle\PromotionBundle\Layout\Extension;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

/**
 * Set isAppliedCouponsAware to context if entity or source entity of checkout
 * is instance of AppliedCouponsAwareInterface
 */
class AppliedCouponsAwareContextConfigurator implements ContextConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $isAppliedCouponsAware = false;
        if ($context->data()->has('checkout')) {
            $checkout = $context->data()->get('checkout');
            $isAppliedCouponsAware = $checkout instanceof CheckoutInterface
                && $checkout instanceof AppliedCouponsAwareInterface
                && !$checkout->getSourceEntity() instanceof QuoteDemand;
        } elseif ($context->data()->has('entity')) {
            $isAppliedCouponsAware = $context->data()->get('entity') instanceof AppliedCouponsAwareInterface;
        }

        $context->getResolver()->setDefault('isAppliedCouponsAware', false);
        $context->set('isAppliedCouponsAware', $isAppliedCouponsAware);
    }
}
