<?php

namespace Oro\Bundle\ProductBundle\Service;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;

/**
 * Manages single unit mode functionality based on system configuration.
 *
 * This service provides access to single unit mode settings, determining whether the system should operate
 * with a single product unit and controlling the visibility of unit codes in the user interface
 * when single unit mode is enabled.
 */
class SingleUnitModeService implements SingleUnitModeServiceInterface
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    #[\Override]
    public function isSingleUnitMode()
    {
        return $this->configManager->get(Configuration::getConfigKeyByName(Configuration::SINGLE_UNIT_MODE));
    }

    #[\Override]
    public function isSingleUnitModeCodeVisible()
    {
        if (!$this->isSingleUnitMode()) {
            return true;
        }
        return $this->configManager->get(Configuration::getConfigKeyByName(Configuration::SINGLE_UNIT_MODE_SHOW_CODE));
    }

    #[\Override]
    public function getDefaultUnitCode()
    {
        return $this->configManager->get(Configuration::getConfigKeyByName(Configuration::DEFAULT_UNIT));
    }
}
