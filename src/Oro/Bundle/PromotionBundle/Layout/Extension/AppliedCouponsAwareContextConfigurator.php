<?php

namespace Oro\Bundle\PromotionBundle\Layout\Extension;

use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
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
            $isAppliedCouponsAware = $context->data()->get('checkout')->getSourceEntity()
                instanceof AppliedCouponsAwareInterface;
        } elseif ($context->data()->has('entity')) {
            $isAppliedCouponsAware = $context->data()->get('entity') instanceof AppliedCouponsAwareInterface;
        }

        $context->getResolver()->setDefault('isAppliedCouponsAware', false);
        $context->set('isAppliedCouponsAware', $isAppliedCouponsAware);
    }
}
