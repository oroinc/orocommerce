<?php

namespace Oro\Bundle\ProductBundle\RelatedItem;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;

class ConfigProvider
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::RELATED_PRODUCTS_ENABLED));
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::MAX_NUMBER_OF_RELATED_PRODUCTS));
    }

    /**
     * @return bool
     */
    public function isBidirectional()
    {
        return $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::RELATED_PRODUCTS_BIDIRECTIONAL));
    }
}
