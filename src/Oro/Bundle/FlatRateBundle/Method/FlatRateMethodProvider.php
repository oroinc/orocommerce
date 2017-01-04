<?php

namespace Oro\Bundle\FlatRateBundle\Method;

use Oro\Bundle\FlatRateBundle\Builder\FlatRateMethodFromChannelBuilder;
use Oro\Bundle\FlatRateBundle\Integration\FlatRateChannelType;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

class FlatRateMethodProvider implements ShippingMethodProviderInterface
{
    /** @var ChannelRepository|null */
    private $channelRepository;

    /** @var FlatRateMethodFromChannelBuilder */
    private $methodBuilder;

    /** @var ShippingMethodInterface[]|array */
    protected $methods;

    /**
     * @param FlatRateMethodFromChannelBuilder $methodBuilder
     */
    public function __construct(FlatRateMethodFromChannelBuilder $methodBuilder)
    {
        $this->methodBuilder = $methodBuilder;
        $this->methods = [];
    }

    /**
     * @param ChannelRepository $channelRepository
     */
    public function setChannelRepository(ChannelRepository $channelRepository)
    {
        $this->channelRepository = $channelRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingMethods()
    {
        if (!$this->methods) {
            $channels = $this->getFlatRateChannels();

            foreach ($channels as $channel) {
                $this->addFlatRateMethod($channel);
            }
        }

        return $this->methods;
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingMethod($name)
    {
        if ($this->hasShippingMethod($name)) {
            return $this->getShippingMethods()[$name];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasShippingMethod($name)
    {
        return array_key_exists($name, $this->getShippingMethods());
    }

    /**
     * @param Channel $channel
     */
    private function addFlatRateMethod(Channel $channel)
    {
        if ($channel->isEnabled()) {
            $method = $this->methodBuilder->build($channel);

            $this->methods[$method->getIdentifier()] = $method;
        }
    }

    /**
     * @return array|Channel[]
     */
    private function getFlatRateChannels()
    {
        if (!$this->channelRepository) {
            return [];
        }

        return $this->channelRepository->findByType(FlatRateChannelType::TYPE);
    }
}
