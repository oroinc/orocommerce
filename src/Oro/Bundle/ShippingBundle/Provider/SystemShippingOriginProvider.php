<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ShippingBundle\Factory\ShippingOriginModelFactory;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;

/**
 * Gets a shipping origin from system configuration.
 */
class SystemShippingOriginProvider
{
    private ConfigManager $configManager;
    private ShippingOriginModelFactory $shippingOriginModelFactory;

    public function __construct(ConfigManager $configManager, ShippingOriginModelFactory $shippingOriginModelFactory)
    {
        $this->configManager = $configManager;
        $this->shippingOriginModelFactory = $shippingOriginModelFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getSystemShippingOrigin(): ShippingOrigin
    {
        return $this->shippingOriginModelFactory->create(
            $this->configManager->get('oro_shipping.shipping_origin') ?: []
        );
    }
}
