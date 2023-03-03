<?php

namespace Oro\Bundle\ShippingBundle\Method;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\ShippingBundle\Provider\MultiShippingCostProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides shipping methods for Multi Shipping feature.
 */
class MultiShippingMethodProvider implements ShippingMethodProviderInterface
{
    public const MULTI_SHIPPING_METHOD_IDENTIFIER = 'multi_shipping';

    private MemoryCacheProviderInterface $memoryCacheProvider;
    private TranslatorInterface $translator;
    private RoundingServiceInterface $roundingService;
    private MultiShippingCostProvider $shippingCostProvider;

    public function __construct(
        MemoryCacheProviderInterface $memoryCacheProvider,
        TranslatorInterface $translator,
        RoundingServiceInterface $roundingService,
        MultiShippingCostProvider $shippingCostProvider
    ) {
        $this->memoryCacheProvider = $memoryCacheProvider;
        $this->translator = $translator;
        $this->roundingService = $roundingService;
        $this->shippingCostProvider = $shippingCostProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingMethods(): array
    {
        return $this->memoryCacheProvider->get(
            'shipping_methods_channel_multi_shipping',
            function () {
                $methods = [];
                $method = $this->createMultiShippingMethod();
                $methods[$method->getIdentifier()] = $method;

                return $methods;
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingMethod(string $name): ?ShippingMethodInterface
    {
        $shippingMethods = $this->getShippingMethods();

        return $shippingMethods[$name] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function hasShippingMethod(string $name): bool
    {
        $shippingMethods = $this->getShippingMethods();

        return isset($shippingMethods[$name]);
    }

    private function createMultiShippingMethod(): MultiShippingMethod
    {
        return new MultiShippingMethod(
            self::MULTI_SHIPPING_METHOD_IDENTIFIER,
            $this->translator->trans('oro.shipping.multi_shipping_method.label'),
            'bundles/oroshipping/img/multi-shipping-logo.png',
            true,
            $this->roundingService,
            $this->shippingCostProvider
        );
    }
}
