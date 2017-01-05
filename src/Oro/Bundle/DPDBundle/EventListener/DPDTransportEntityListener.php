<?php

namespace Oro\Bundle\DPDBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethod;
use Oro\Bundle\DPDBundle\Provider\ChannelType;

class DPDTransportEntityListener
{
    /**
     * @param DPDTransport $transport
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(DPDTransport $transport, LifecycleEventArgs $args)
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
                $shippingMethodIdentifier = DPDShippingMethod::IDENTIFIER . '_' . $channel->getId();
                $configuredMethods = $entityManager
                    ->getRepository('OroShippingBundle:ShippingMethodConfig')
                    ->findBy(['method' => $shippingMethodIdentifier ]);
                if (0 < count($configuredMethods)) {
                    $types = $entityManager
                        ->getRepository('OroShippingBundle:ShippingMethodTypeConfig')
                        ->findBy(['methodConfig' => $configuredMethods, 'type' => $deleted]);

                    foreach ($types as $type) {
                        $entityManager->getRepository('OroShippingBundle:ShippingMethodTypeConfig')
                            ->deleteByMethodAndType($type->getMethodConfig(), $type->getType());
                    }
                }
            }
        }
    }
}
