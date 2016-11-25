<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class UnitModeProvider
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
     * @return bool
     */
    public function isSingleUnitMode()
    {
        return $this->configManager->get('oro_product.single_unit_mode');
    }

    /**
     * @return bool
     */
    public function isSingleUnitModeCodeVisible()
    {
        return $this->configManager->get('oro_product.single_unit_mode_show_code');
    }
}
