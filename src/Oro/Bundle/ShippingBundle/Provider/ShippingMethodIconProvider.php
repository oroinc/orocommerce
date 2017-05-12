<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodIconAwareInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class ShippingMethodIconProvider implements ShippingMethodIconProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ShippingMethodRegistry
     */
    private $methodRegistry;

    /**
     * @param ShippingMethodRegistry $methodRegistry
     */
    public function __construct(ShippingMethodRegistry $methodRegistry)
    {
        $this->methodRegistry = $methodRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function getIcon($identifier)
    {
        if ($this->methodRegistry->hasShippingMethod($identifier)) {
            $method = $this->methodRegistry->getShippingMethod($identifier);

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
