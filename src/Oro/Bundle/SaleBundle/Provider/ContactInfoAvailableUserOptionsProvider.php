<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SaleBundle\DependencyInjection\Configuration;

class ContactInfoAvailableUserOptionsProvider implements OptionsProviderInterface
{
    const DON_T_DISPLAY_CONTACT_INFO = 'dont_display';
    const USE_USER_PROFILE_DATA = 'user_profile_data';
    const ENTER_MANUALLY = 'enter_manually';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions()
    {
        return [
            self::DON_T_DISPLAY_CONTACT_INFO,
            self::USE_USER_PROFILE_DATA,
            self::ENTER_MANUALLY,
        ];
    }

    /**
     * @return array
     */
    public function getSelectedOptions()
    {
        $key = Configuration::getConfigKeyByName(Configuration::AVAILABLE_USER_OPTIONS);
        $options = $this->configManager->get($key);
        if (empty($options)) {
            $options = $this->getOptions();
        }

        return $options;
    }
}
