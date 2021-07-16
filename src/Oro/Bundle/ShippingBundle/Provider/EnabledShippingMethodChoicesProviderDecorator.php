<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

class EnabledShippingMethodChoicesProviderDecorator implements ShippingMethodChoicesProviderInterface
{
    /**
     * @var ShippingMethodProviderInterface
     */
    protected $shippingMethodProvider;

    /**
     * @var ShippingMethodChoicesProviderInterface
     */
    protected $provider;

    public function __construct(
        ShippingMethodProviderInterface $shippingMethodProvider,
        ShippingMethodChoicesProviderInterface $provider
    ) {
        $this->shippingMethodProvider = $shippingMethodProvider;
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethods($translate = false)
    {
        $methods = $this->provider->getMethods($translate);
        $enabledMethods = [];
        foreach ($methods as $label => $methodId) {
            $method = $this->shippingMethodProvider->getShippingMethod($methodId);
            if ($method->isEnabled()) {
                $enabledMethods[$label] = $methodId;
            }
        }

        return $enabledMethods;
    }
}
