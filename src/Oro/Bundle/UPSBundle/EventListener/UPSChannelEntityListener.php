<?php

namespace Oro\Bundle\UPSBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethod;

class UPSChannelEntityListener
{
    /**
     * @param Channel $channel
     * @param LifecycleEventArgs $args
     */
    public function preRemove(Channel $channel, LifecycleEventArgs $args)
    {
        if ('ups' === $channel->getType()) {
            $entityManager = $args->getEntityManager();
            $shippingMethodIdentifier = UPSShippingMethod::IDENTIFIER . '_' . $channel->getId();
            $configuredMethods = $entityManager
                ->getRepository('OroShippingBundle:ShippingRuleMethodConfig')
                ->findBy(['method' => $shippingMethodIdentifier,]);

            foreach ($configuredMethods as $configuredMethod) {
                $entityManager->getRepository('OroShippingBundle:ShippingRuleMethodConfig')
                    ->deleteByMethod($configuredMethod->getMethod());
            }
            $entityManager->getRepository('OroShippingBundle:ShippingRule')
                ->disableRulesWithoutShippingMethods();
        }
    }
}
