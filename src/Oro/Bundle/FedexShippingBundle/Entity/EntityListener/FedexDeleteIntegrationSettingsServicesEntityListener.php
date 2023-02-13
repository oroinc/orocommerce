<?php

namespace Oro\Bundle\FedexShippingBundle\Entity\EntityListener;

use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;
use Oro\Bundle\FedexShippingBundle\Integration\FedexChannel;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\Identifier\FedexMethodTypeIdentifierGeneratorInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\ShippingBundle\Method\Event\MethodTypeRemovalEventDispatcherInterface;

/**
 * Listens to FedexIntegrationSettings Entity update event
 * When some services are deleted, removes also connected services to them
 */
class FedexDeleteIntegrationSettingsServicesEntityListener
{
    /**
     * @var IntegrationIdentifierGeneratorInterface
     */
    private $integrationIdentifierGenerator;

    /**
     * @var FedexMethodTypeIdentifierGeneratorInterface
     */
    private $typeIdentifierGenerator;

    /**
     * @var MethodTypeRemovalEventDispatcherInterface
     */
    private $typeRemovalEventDispatcher;

    public function __construct(
        IntegrationIdentifierGeneratorInterface $integrationIdentifierGenerator,
        FedexMethodTypeIdentifierGeneratorInterface $typeIdentifierGenerator,
        MethodTypeRemovalEventDispatcherInterface $typeRemovalEventDispatcher
    ) {
        $this->integrationIdentifierGenerator = $integrationIdentifierGenerator;
        $this->typeIdentifierGenerator = $typeIdentifierGenerator;
        $this->typeRemovalEventDispatcher = $typeRemovalEventDispatcher;
    }

    public function postUpdate(FedexIntegrationSettings $settings, LifecycleEventArgs $args)
    {
        /** @var PersistentCollection $services */
        $services = $settings->getShippingServices();

        $deletedServices = $services->getDeleteDiff();
        if (empty($deletedServices)) {
            return;
        }

        $channel = $args->getObjectManager()
            ->getRepository('OroIntegrationBundle:Channel')
            ->findOneBy([
                'type' => FedexChannel::TYPE,
                'transport' => $settings
            ]);

        if (null === $channel) {
            return;
        }

        $this->dispatchTypeRemovalEvents($deletedServices, $channel);
    }

    /**
     * @param FedexShippingService[] $deletedServices
     * @param Channel                $channel
     */
    private function dispatchTypeRemovalEvents(array $deletedServices, Channel $channel)
    {
        $methodId = $this->integrationIdentifierGenerator->generateIdentifier($channel);

        foreach ($deletedServices as $service) {
            $typeId = $this->typeIdentifierGenerator->generate($service);
            $this->typeRemovalEventDispatcher->dispatch($methodId, $typeId);
        }
    }
}
