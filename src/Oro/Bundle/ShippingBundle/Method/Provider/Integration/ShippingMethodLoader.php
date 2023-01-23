<?php

namespace Oro\Bundle\ShippingBundle\Method\Provider\Integration;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;

/**
 * The service to load a specific type of shipping methods.
 */
class ShippingMethodLoader
{
    private ChannelLoaderInterface $channelLoader;
    private MemoryCacheProviderInterface $memoryCacheProvider;

    public function __construct(
        ChannelLoaderInterface $channelLoader,
        MemoryCacheProviderInterface $memoryCacheProvider
    ) {
        $this->channelLoader = $channelLoader;
        $this->memoryCacheProvider = $memoryCacheProvider;
    }

    /**
     * @param string                                    $channelType
     * @param IntegrationShippingMethodFactoryInterface $shippingMethodFactory
     *
     * @return ShippingMethodInterface[] [shipping method identifier => shipping method, ...]
     */
    public function loadShippingMethods(
        string $channelType,
        IntegrationShippingMethodFactoryInterface $shippingMethodFactory
    ): array {
        return $this->memoryCacheProvider->get(
            'shipping_methods_channel_' . $channelType,
            function () use ($channelType, $shippingMethodFactory) {
                $shippingMethods = [];
                $channels = $this->channelLoader->loadChannels($channelType, true);
                foreach ($channels as $channel) {
                    $method = $shippingMethodFactory->create($channel);
                    $shippingMethods[$method->getIdentifier()] = $method;
                }

                return $shippingMethods;
            }
        );
    }
}
