<?php

namespace OroB2B\Bundle\PricingBundle\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

class EnabledCurrenciesProvider implements DataProviderInterface
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'orob2b_pricing_enabled_currencies';
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        return $this->configManager->get('oro_b2b_pricing.enabled_currencies', []);
    }
}
