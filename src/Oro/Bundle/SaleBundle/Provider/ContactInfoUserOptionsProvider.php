<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\SaleBundle\Provider\ContactInfoAvailableUserOptionsProvider as UserOptionsProvider;
use Oro\Bundle\SaleBundle\Provider\ContactInfoSourceOptionsProvider as SourceOptionsProvider;

class ContactInfoUserOptionsProvider implements OptionProviderWithDefaultValueInterface
{
    const USE_SYSTEM = 'use_system';

    /**
     * @var ContactInfoSourceOptionsProvider
     */
    protected $contactInfoCustomerOptionsProvider;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var ContactInfoAvailableUserOptionsProvider
     */
    protected $availableUserOptionsProvider;

    public function __construct(
        ConfigManager $configManager,
        ContactInfoAvailableUserOptionsProvider $availableUserOptionsProvider,
        ContactInfoSourceOptionsProvider $contactInfoCustomerOptionsProvider
    ) {
        $this->configManager = $configManager;
        $this->availableUserOptionsProvider = $availableUserOptionsProvider;
        $this->contactInfoCustomerOptionsProvider = $contactInfoCustomerOptionsProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions()
    {
        $options = $this->availableUserOptionsProvider->getSelectedOptions();
        if ($this->contactInfoCustomerOptionsProvider->isSelectedOptionPreConfigured()) {
            $options[] = self::USE_SYSTEM;
        }

        return $options;
    }

    /**
     * @param null $scopeIdentifier
     *
     * @return string
     */
    public function getSelectedOption($scopeIdentifier = null)
    {
        $key = Configuration::getConfigKeyByName(Configuration::CONTACT_INFO_USER_OPTION);
        $option = $this->configManager->get($key, false, false, $scopeIdentifier);
        if (!in_array($option, $this->getOptions(), true)) {
            $option = $this->getDefaultOption();
        }

        return $option;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultOption()
    {
        $map = [
            SourceOptionsProvider::DONT_DISPLAY => UserOptionsProvider::DON_T_DISPLAY_CONTACT_INFO,
            SourceOptionsProvider::PRE_CONFIGURED => self::USE_SYSTEM,
            SourceOptionsProvider::CUSTOMER_USER_OWNER => UserOptionsProvider::USE_USER_PROFILE_DATA,
            SourceOptionsProvider::CUSTOMER_OWNER => UserOptionsProvider::USE_USER_PROFILE_DATA,
        ];
        $selectedOption = $this->contactInfoCustomerOptionsProvider->getSelectedOption();

        return array_key_exists($selectedOption, $map) ? $map[$selectedOption] : array_shift($map);
    }
}
