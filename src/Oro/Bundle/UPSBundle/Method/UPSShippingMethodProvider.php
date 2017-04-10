<?php

namespace Oro\Bundle\UPSBundle\Method;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\UPSBundle\Provider\ChannelType;

class UPSShippingMethodProvider implements ShippingMethodProviderInterface
{
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
     * @param DoctrineHelper $doctrineHelper
     * @param IntegrationShippingMethodFactoryInterface $methodFactory
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        IntegrationShippingMethodFactoryInterface $methodFactory
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->methodFactory = $methodFactory;
    }

    /**
     * @param Channel            $channel
     * @param LifecycleEventArgs $event
     */
    public function postLoad(Channel $channel, LifecycleEventArgs $event)
    {
        if ($channel->getType() === ChannelType::TYPE) {
            $this->loadedChannels[] = $channel;
            $this->createMethodFromChannel($channel);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingMethods()
    {
        if (empty($this->methods)) {
            foreach ($this->getChannels() as $channel) {
                $this->createMethodFromChannel($channel);
            }
        }

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

    /**
     * @return ChannelRepository|\Doctrine\ORM\EntityRepository
     */
    private function getRepository()
    {
        return $this->doctrineHelper->getEntityRepository('OroIntegrationBundle:Channel');
    }

    /**
     * @return Channel[]
     */
    private function getChannels()
    {
        $this->getRepository()->findByTypeAndExclude(ChannelType::TYPE, $this->loadedChannels);
        /* After fetching all entities will be saved into $loadedChannels on static::postLoad call */
        return $this->loadedChannels;
    }
}
