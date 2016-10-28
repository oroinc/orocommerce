<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ShippingBundle\Factory\ShippingOriginModelFactory;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;

class ShippingOriginProvider
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var ShippingOriginModelFactory */
    protected $shippingOriginModelFactory;

    /**
     * @param ConfigManager $configManager
     * @param ShippingOriginModelFactory $shippingOriginModelFactory
     */
    public function __construct(ConfigManager $configManager, ShippingOriginModelFactory $shippingOriginModelFactory)
    {
        $this->configManager = $configManager;
        $this->shippingOriginModelFactory = $shippingOriginModelFactory;
    }

    /**
     * @return ShippingOrigin
     */
    public function getSystemShippingOrigin()
    {
        $configData = $this->configManager->get('oro_shipping.shipping_origin') ?: [];

        return $this->shippingOriginModelFactory->create($configData);
    }
}
