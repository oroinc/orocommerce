<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * Provider for getting configuration value if open orders page should be displayed
 * separately.
 */
class OpenOrdersSeparatePageConfigProvider
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * OpenOrdersSeparatePageConfigProvider constructor.
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return string
     */
    public function getOpenOrdersSeparatePageConfig()
    {
        return $this
            ->configManager
            ->get('oro_b2b_checkout.frontend_open_orders_separate_page');
    }
}
