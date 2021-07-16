<?php

namespace Oro\Bundle\MoneyOrderBundle\Migrations\Data\ORM\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

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
