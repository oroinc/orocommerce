<?php

namespace Oro\Bundle\MoneyOrderBundle\Migrations\Data\ORM\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * Creates Money Order configuration objects from system configuration during data migrations.
 *
 * This factory loads Money Order settings from the system configuration manager and creates
 * {@see MoneyOrderConfig} objects for use in data migration processes. It provides a convenient way
 * to access legacy Money Order configuration values during the migration from system config
 * to integration-based settings.
 */
class MoneyOrderConfigFactory
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
     * @return MoneyOrderConfig
     */
    public function createMoneyOrderConfig()
    {
        return new MoneyOrderConfig($this->configManager);
    }
}
