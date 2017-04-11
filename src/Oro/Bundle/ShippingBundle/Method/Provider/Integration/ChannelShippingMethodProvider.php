<?php

namespace Oro\Bundle\ShippingBundle\Method\Provider\Integration;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

class ChannelShippingMethodProvider implements ShippingMethodProviderInterface
{
    /**
     * @var string
     */
    private $channelType;

    /**
     * @var ChannelRepository
     */
    private $channelRepository;

    /**
     * @var IntegrationShippingMethodFactoryInterface
     */
    private $methodFactory;

    /**
     * @var ShippingMethodInterface[]
     */
    private $methods = [];

    /**
     * @var Channel[]
     */
    private $loadedChannels = [];

    /**
     * @param string                                    $channelType
     * @param ChannelRepository                         $channelRepository
     * @param IntegrationShippingMethodFactoryInterface $methodFactory
     */
    public function __construct(
        string $channelType,
        ChannelRepository $channelRepository,
        IntegrationShippingMethodFactoryInterface $methodFactory
    ) {
        $this->channelType = $channelType;
        $this->channelRepository = $channelRepository;
        $this->methodFactory = $methodFactory;
    }

    /**
     * We need only non dirty channels for creating methods.
     * For example if entity was changed on form submit, we will have dirty channel in Unit of work.
     *
     * @param Channel            $channel
     * @param LifecycleEventArgs $event
     */
    public function postLoad(Channel $channel, LifecycleEventArgs $event)
    {
        if ($channel->getType() === $this->channelType) {
            $this->loadedChannels[] = $channel;
            $this->createMethodFromChannel($channel);
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
        return array_key_exists($name, $this->getShippingMethods());
    }

    /**
     * @param Channel $channel
     */
    private function createMethodFromChannel(Channel $channel)
    {
        $method = $this->methodFactory->create($channel);
        $this->methods[$method->getIdentifier()] = $method;
    }

    private function loadChannels()
    {
        /* After fetching, all entities will be saved into $loadedChannels on postLoad call */
        $this->channelRepository->findByTypeAndExclude($this->channelType, $this->loadedChannels);
    }
}
