<?php

namespace Oro\Bundle\UPSBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Provider\ChannelType;

/**
 * Handle product scalar attributes change that may affect prices recalculation.
 */
class UPSChannelEntityListener
{
    /**
     * @var ShippingMethodRegistry
     */
    protected $registry;

    /**
     * @param ShippingMethodRegistry $registry
     */
    public function __construct(ShippingMethodRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param Channel $channel
     * @param LifecycleEventArgs $args
     */
    public function preRemove(Channel $channel, LifecycleEventArgs $args)
    {
        if ('ups' === $channel->getType()) {
            $entityManager = $args->getEntityManager();
            $shippingMethods = $this->registry->getShippingMethods();
            foreach ($shippingMethods as $shippingMethod) {
                if ($shippingMethod->getLabel() === $channel->getName()) {
                    $identifier = $shippingMethod->getIdentifier();
                    $configuredMethods = $entityManager
                        ->getRepository('OroShippingBundle:ShippingRuleMethodConfig')
                        ->findBy(['method' => $identifier,]);

                    foreach ($configuredMethods as $configuredMethod) {
                        $entityManager->getRepository('OroShippingBundle:ShippingRuleMethodConfig')
                            ->deleteByMethod($configuredMethod->getMethod());
                    }
                    break;
                }
            }
        }
    }
}
