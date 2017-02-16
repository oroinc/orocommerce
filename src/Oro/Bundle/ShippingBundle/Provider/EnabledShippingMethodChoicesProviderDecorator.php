<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;

class EnabledShippingMethodChoicesProviderDecorator
{
    /**
     * @var ShippingMethodRegistry
     */
    protected $methodRegistry;
    
    /**
     * @var ShippingMethodChoicesProviderInterface
     */
    protected $provider;

    /**
     * @param ShippingMethodRegistry                 $methodRegistry
     * @param ShippingMethodChoicesProviderInterface $provider
     */
    public function __construct(
        ShippingMethodRegistry $methodRegistry,
        ShippingMethodChoicesProviderInterface $provider
    ) {
        $this->methodRegistry = $methodRegistry;
        $this->provider = $provider;
    }
    /**
     * {@inheritdoc}
     */
    public function getMethods($translate = false)
    {
        $methods = $this->provider->getMethods($translate);
        $methodEnabled = [];
        foreach ($methods as $methodId => $label) {
            $method = $this->methodRegistry->getShippingMethod($methodId);
            if ($method->isEnabled()) {
                $methodEnabled[$methodId] =  $label;
            }
        }

        return $methodEnabled;
    }
}
