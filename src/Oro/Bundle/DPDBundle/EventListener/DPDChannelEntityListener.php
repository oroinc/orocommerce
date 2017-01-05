<?php

namespace Oro\Bundle\DPDBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethod;

class DPDChannelEntityListener
{
    /**
     * @param Channel $channel
     * @param LifecycleEventArgs $args
     */
    public function preRemove(Channel $channel, LifecycleEventArgs $args)
    {
        if ('dpd' === $channel->getType()) {
            $entityManager = $args->getEntityManager();
            $shippingMethodIdentifier = DPDShippingMethod::IDENTIFIER . '_' . $channel->getId();
            $configuredMethods = $entityManager
                ->getRepository('OroShippingBundle:ShippingMethodConfig')
                ->findBy(['method' => $shippingMethodIdentifier]);

            foreach ($configuredMethods as $configuredMethod) {
                $entityManager->getRepository('OroShippingBundle:ShippingMethodConfig')
                    ->deleteByMethod($configuredMethod->getMethod());
            }
            $entityManager->getRepository('OroShippingBundle:ShippingMethodsConfigsRule')
                ->disableRulesWithoutShippingMethods();
        }
    }
}
