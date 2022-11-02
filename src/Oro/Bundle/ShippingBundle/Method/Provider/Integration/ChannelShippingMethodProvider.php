<?php

namespace Oro\Bundle\ShippingBundle\Method\Provider\Integration;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
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
     * @var DoctrineHelper
     */
    private $doctrineHelper;

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
     * @param DoctrineHelper                            $doctrineHelper
     * @param IntegrationShippingMethodFactoryInterface $methodFactory
     */
    public function __construct(
        $channelType,
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

    private function createMethodFromChannel(Channel $channel)
    {
        $method = $this->methodFactory->create($channel);
        $this->methods[$method->getIdentifier()] = $method;
    }

    /**
     * @return ChannelRepository|\Doctrine\ORM\EntityRepository
     */
    private function getRepository()
    {
        return $this->doctrineHelper->getEntityRepository('OroIntegrationBundle:Channel');
    }

    private function loadChannels()
    {
        /* After fetching, all entities will be saved into $loadedChannels on postLoad call */
        $this->getRepository()->findByTypeAndExclude($this->channelType, $this->loadedChannels);
    }
}
