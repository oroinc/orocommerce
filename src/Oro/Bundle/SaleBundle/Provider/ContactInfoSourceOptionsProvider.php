<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SaleBundle\DependencyInjection\Configuration;

class ContactInfoSourceOptionsProvider implements OptionsProviderInterface
{
    const DONT_DISPLAY = 'dont_display';
    const CUSTOMER_USER_OWNER = 'customer_user_owner';
    const CUSTOMER_OWNER = 'customer_owner';
    const PRE_CONFIGURED = 'pre_configured';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return [
            self::DONT_DISPLAY,
            self::CUSTOMER_USER_OWNER,
            self::CUSTOMER_OWNER,
            self::PRE_CONFIGURED,
        ];
    }

    /**
     * @return string
     */
    public function getSelectedOption()
    {
        $key = Configuration::getConfigKeyByName(Configuration::CONTACT_INFO_SOURCE_DISPLAY);

        return $this->configManager->get($key);
    }

    /**
     * @return bool
     */
    public function isSelectedOptionPreConfigured()
    {
        return $this->getSelectedOption() === self::PRE_CONFIGURED;
    }
}
