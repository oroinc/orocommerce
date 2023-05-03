<?php

namespace Oro\Bundle\ShippingBundle\Method\Provider\Integration;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;

/**
 * The service to load a specific type of shipping methods.
 */
class ShippingMethodLoader
{
    private ChannelLoaderInterface $channelLoader;
    private MemoryCacheProviderInterface $memoryCacheProvider;
    private ShippingMethodOrganizationProvider $organizationProvider;
    private TokenAccessorInterface $tokenAccessor;

    public function __construct(
        ChannelLoaderInterface $channelLoader,
        MemoryCacheProviderInterface $memoryCacheProvider,
        ShippingMethodOrganizationProvider $organizationProvider,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->channelLoader = $channelLoader;
        $this->memoryCacheProvider = $memoryCacheProvider;
        $this->organizationProvider = $organizationProvider;
        $this->tokenAccessor = $tokenAccessor;
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
            $this->getCacheKey($channelType),
            function () use ($channelType, $shippingMethodFactory) {
                $shippingMethods = [];
                $channels = $this->channelLoader->loadChannels(
                    $channelType,
                    true,
                    $this->organizationProvider->getOrganization()
                );
                foreach ($channels as $channel) {
                    $method = $shippingMethodFactory->create($channel);
                    $shippingMethods[$method->getIdentifier()] = $method;
                }

                return $shippingMethods;
            }
        );
    }

    private function getCacheKey(string $channelType): string
    {
        return sprintf(
            'shipping_methods_channel_%s_%s',
            $channelType,
            $this->organizationProvider->getOrganizationId() ?? $this->tokenAccessor->getOrganizationId()
        );
    }
}
