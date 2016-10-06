<?php

namespace Oro\Bundle\UPSBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethod;
use Oro\Bundle\UPSBundle\Provider\ChannelType;

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
            $channel = $entityManager
                ->getRepository('OroIntegrationBundle:Channel')
                ->findOneBy(['type' => ChannelType::TYPE, 'transport' => $transport->getId()]);

            if (null !== $channel) {
                $shippingMethodIdentifier = UPSShippingMethod::IDENTIFIER . '_' . $channel->getId();
                if (null !== $this->registry->getShippingMethod($shippingMethodIdentifier)) {
                    $configuredMethods = $entityManager
                        ->getRepository('OroShippingBundle:ShippingRuleMethodConfig')
                        ->findBy(['method' => $shippingMethodIdentifier ]);
                    if (0 < count($configuredMethods)) {
                        $types = $entityManager
                            ->getRepository('OroShippingBundle:ShippingRuleMethodTypeConfig')
                            ->findBy(['methodConfig' => $configuredMethods, 'type' => $deleted]);

                        foreach ($types as $type) {
                            $entityManager->getRepository('OroShippingBundle:ShippingRuleMethodTypeConfig')
                                ->deleteByMethodAndType($type->getMethodConfig(), $type->getType());
                        }
                    }
                }
            }
        }
    }
}
