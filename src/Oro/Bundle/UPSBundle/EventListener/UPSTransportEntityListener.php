<?php

namespace Oro\Bundle\UPSBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\ShippingBundle\Method\Event\MethodTypeRemovalEventDispatcherInterface;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Method\Identifier\UPSMethodTypeIdentifierGeneratorInterface;
use Oro\Bundle\UPSBundle\Provider\ChannelType;

class UPSTransportEntityListener
{
    /**
     * @var IntegrationMethodIdentifierGeneratorInterface
     */
    private $methodIdentifierGenerator;

    /**
     * @var UPSMethodTypeIdentifierGeneratorInterface
     */
    private $typeIdentifierGenerator;

    /**
     * @var MethodTypeRemovalEventDispatcherInterface
     */
    private $typeRemovalEventDispatcher;

    /**
     * @param IntegrationMethodIdentifierGeneratorInterface $methodIdentifierGenerator
     * @param UPSMethodTypeIdentifierGeneratorInterface $typeIdentifierGenerator
     * @param MethodTypeRemovalEventDispatcherInterface $typeRemovalEventDispatcher
     */
    public function __construct(
        IntegrationMethodIdentifierGeneratorInterface $methodIdentifierGenerator,
        UPSMethodTypeIdentifierGeneratorInterface $typeIdentifierGenerator,
        MethodTypeRemovalEventDispatcherInterface $typeRemovalEventDispatcher
    ) {
        $this->methodIdentifierGenerator = $methodIdentifierGenerator;
        $this->typeIdentifierGenerator = $typeIdentifierGenerator;
        $this->typeRemovalEventDispatcher = $typeRemovalEventDispatcher;
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
            $entityManager = $args->getEntityManager();
            $channel = $entityManager
                ->getRepository('OroIntegrationBundle:Channel')
                ->findOneBy(['type' => ChannelType::TYPE, 'transport' => $transport->getId()]);

            if (null !== $channel) {
                foreach ($deletedServices as $deletedService) {
                    $methodId = $this->methodIdentifierGenerator->generateIdentifier($channel);
                    $typeId = $this->typeIdentifierGenerator->generateIdentifier($channel, $deletedService);
                    $this->typeRemovalEventDispatcher->dispatch($methodId, $typeId);
                }
            }
        }
    }
}
