<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;

/**
 * Provider for getting configuration value if open orders page should be displayed
 * separately.
 */
class OpenOrdersSeparatePageConfigProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * OpenOrdersSeparatePageConfigProvider constructor.
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        return $this
            ->configManager
            ->get('oro_b2b_checkout.frontend_open_orders_separate_page');
    }
}
