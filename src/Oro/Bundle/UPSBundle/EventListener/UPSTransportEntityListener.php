<?php

namespace Oro\Bundle\UPSBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;

class UPSTransportEntityListener
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
     * @param UPSTransport $transport
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(UPSTransport $transport, LifecycleEventArgs $args)
    {
        /** @var PersistentCollection $services */
        $services = $transport->getApplicableShippingServices();
        $deletedServices = $services->getDeleteDiff();
        if (0 !== count($deletedServices)) {
            $deleted = [];
            /** @var ShippingService $deletedService */
            foreach ($deletedServices as $deletedService) {
                $deleted[] = $deletedService->getCode();
            }
            $entityManager = $args->getEntityManager();
            $upsChannels = $entityManager
                ->getRepository('OroIntegrationBundle:Channel')
                ->findBy(['type' => ChannelType::TYPE]);
            $label = null;
            foreach ($upsChannels as $upsChannel) {
                if ($upsChannel->getTransport()->getId() === $transport->getId()) {
                    $label = $upsChannel->getName();
                    break;
                }
            }
            if ($label !== null) {
                $shippingMethods = $this->registry->getShippingMethods();
                foreach ($shippingMethods as $shippingMethod) {
                    if ($shippingMethod->getLabel() === $label) {
                        $identifier = $shippingMethod->getIdentifier();
                        $configuredMethods = $entityManager
                            ->getRepository('OroShippingBundle:ShippingRuleMethodConfig')
                            ->findBy(['method' => $identifier, ]);
                        $types = $entityManager
                            ->getRepository('OroShippingBundle:ShippingRuleMethodTypeConfig')
                            ->findBy(['methodConfig' => $configuredMethods, 'type' => $deleted]);

                        foreach ($types as $type) {
                            $entityManager->getRepository('OroShippingBundle:ShippingRuleMethodTypeConfig')
                                ->deleteByMethodAndType($type->getMethodConfig(), $type->getType());
                        }
                        break;
                    }
                }
            }
        }
    }
}
