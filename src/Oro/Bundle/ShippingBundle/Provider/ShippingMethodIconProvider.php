<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodIconAwareInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * A service to get an icon for a shipping method.
 */
class ShippingMethodIconProvider implements ShippingMethodIconProviderInterface
{
    private ShippingMethodProviderInterface $shippingMethodProvider;
    private LoggerInterface $logger;

    public function __construct(
        ShippingMethodProviderInterface $shippingMethodProvider,
        LoggerInterface $logger
    ) {
        $this->shippingMethodProvider = $shippingMethodProvider;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function getIcon(string $identifier): ?string
    {
        if ($this->shippingMethodProvider->hasShippingMethod($identifier)) {
            $method = $this->shippingMethodProvider->getShippingMethod($identifier);
            if ($method instanceof ShippingMethodIconAwareInterface) {
                return $method->getIcon();
            }
        } else {
            $this->logger->warning(sprintf('Requested icon for non-existing shipping method "%s"', $identifier));
        }

        return null;
    }
}
