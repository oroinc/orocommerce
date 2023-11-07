<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

/**
 * Provides an array of enabled shipping methods.
 */
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

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function __construct(
        ShippingMethodProviderInterface $shippingMethodProvider,
        ShippingMethodChoicesProviderInterface $provider
    ) {
        $this->shippingMethodProvider = $shippingMethodProvider;
        $this->provider = $provider;
    }

    public function setDoctrineHelper(DoctrineHelper $doctrineHelper): void
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethods($translate = false)
    {
        $methods = $this->provider->getMethods($translate);
        $enabledMethods = [];
        foreach ($methods as $methodId) {
            $method = $this->shippingMethodProvider->getShippingMethod($methodId);
            if ($method->isEnabled()) {
                $label = $this->getLabel($method);
                //cannot guarantee uniqueness of shipping name
                //need to be sure that we wouldn't override exists one
                while (isset($enabledMethods[$label])) {
                    $label .= ' ';
                }
                $enabledMethods[$label] = $methodId;
            }
        }

        return $enabledMethods;
    }

    protected function getLabel(ShippingMethodInterface $method): string
    {
        return $this->loadChannel($method->getIdentifier())?->getName() ?: $method->getLabel();
    }

    private function loadChannel(string $identifier): ?Channel
    {
        //extract entity identifier flat_rate_4 => 4
        $id = substr($identifier, strrpos($identifier, '_') + 1);
        return $this->doctrineHelper?->getEntity(Channel::class, $id);
    }
}
