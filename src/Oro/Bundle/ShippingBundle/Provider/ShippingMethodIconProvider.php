<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodIconAwareInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class ShippingMethodIconProvider implements ShippingMethodIconProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ShippingMethodProviderInterface
     */
    private $shippingMethodProvider;

    public function __construct(ShippingMethodProviderInterface $shippingMethodProvider)
    {
        $this->shippingMethodProvider = $shippingMethodProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getIcon($identifier)
    {
        if ($this->shippingMethodProvider->hasShippingMethod($identifier)) {
            $method = $this->shippingMethodProvider->getShippingMethod($identifier);

            if ($method instanceof ShippingMethodIconAwareInterface) {
                return $method->getIcon();
            }
        } else {
            if ($this->logger) {
                $msg = sprintf('Requested icon for non-existing shipping method "%s"', $identifier);
                $this->logger->warning($msg);
            }
        }

        return null;
    }
}
