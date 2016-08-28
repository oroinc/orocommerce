<?php

namespace Oro\Bundle\ShippingBundle\Factory;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;

class ShippingContextFactory
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(
        ConfigManager $configManager
    ) {
        $this->configManager = $configManager;
    }

    /**
     * Sets defaults for Shipping Context
     *
     * @return ShippingContext
     */
    public function create()
    {
        $shippingContext = new ShippingContext();

        $shippingContext->setShippingOrigin($this->configManager->get('oro_config.global'));

        return $shippingContext;
    }
}
