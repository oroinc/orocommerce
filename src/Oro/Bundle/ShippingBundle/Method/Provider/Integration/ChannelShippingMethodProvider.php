<?php

namespace Oro\Bundle\ShippingBundle\Method\Provider\Integration;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

/**
 * Provides shipping methods for a specific integration channel type.
 */
class ChannelShippingMethodProvider implements ShippingMethodProviderInterface
{
    private string $channelType;
    private DoctrineHelper $doctrineHelper;
    private IntegrationShippingMethodFactoryInterface $methodFactory;
    /** @var ShippingMethodInterface[] */
    private array $methods = [];
    /** @var Channel[] */
    private array $loadedChannels = [];

    public function __construct(
        string $channelType,
        DoctrineHelper $doctrineHelper,
        IntegrationShippingMethodFactoryInterface $methodFactory
    ) {
        $this->channelType = $channelType;
        $this->doctrineHelper = $doctrineHelper;
        $this->methodFactory = $methodFactory;
    }

    /**
     * We need only non dirty channels for creating methods.
     * For example if entity was changed on form submit, we will have dirty channel in Unit of work.
     */
    public function postLoad(Channel $channel): void
    {
        if ($channel->getType() === $this->channelType) {
            $this->loadedChannels[] = $channel;
            $method = $this->methodFactory->create($channel);
            $this->methods[$method->getIdentifier()] = $method;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingMethods()
    {
        $this->loadChannels();

        return $this->methods;
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingMethod($name)
    {
        if ($this->hasShippingMethod($name)) {
            return $this->getShippingMethods()[$name];
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function hasShippingMethod($name)
    {
        return \array_key_exists($name, $this->getShippingMethods());
    }

    private function loadChannels(): void
    {
        /** @var ChannelRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(Channel::class);
        /* After fetching, all entities will be saved into $loadedChannels on postLoad call */
        $repository->findByTypeAndExclude($this->channelType, $this->loadedChannels);
    }
}
