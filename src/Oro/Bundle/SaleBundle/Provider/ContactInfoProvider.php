<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserInterface;
use Oro\Bundle\SaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\SaleBundle\Model\ContactInfo;
use Oro\Bundle\SaleBundle\Model\ContactInfoFactoryInterface;
use Oro\Bundle\SaleBundle\Provider\ContactInfoAvailableUserOptionsProvider as UserOptionsProvider;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Get Contact Information according to configuration.
 */
class ContactInfoProvider implements ContactInfoProviderInterface
{
    public const USER_CONFIG_RELATED_OPTIONS = [
        UserOptionsProvider::DON_T_DISPLAY_CONTACT_INFO,
        UserOptionsProvider::USE_USER_PROFILE_DATA,
        UserOptionsProvider::ENTER_MANUALLY
    ];

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var ContactInfoSourceOptionsProvider
     */
    protected $sourceOptionsProvider;

    /**
     * @var ContactInfoUserOptionsProvider
     */
    protected $userContactInfoOptionProvider;

    /**
     * @var ContactInfoFactoryInterface
     */
    protected $contactInfoFactory;

    public function __construct(
        ConfigManager $configManager,
        ContactInfoSourceOptionsProvider $sourceOptionsProvider,
        ContactInfoUserOptionsProvider $userContactInfoProvider,
        ContactInfoFactoryInterface $contactInfoFactory
    ) {
        $this->configManager = $configManager;
        $this->sourceOptionsProvider = $sourceOptionsProvider;
        $this->userContactInfoOptionProvider = $userContactInfoProvider;
        $this->contactInfoFactory = $contactInfoFactory;
    }

    /**
     * @param CustomerUserInterface|null $customerUser
     *
     * @return ContactInfo
     */
    public function getContactInfo(CustomerUserInterface $customerUser = null)
    {
        $contactInfo = $this->getContactInformationByUserConfiguration($customerUser);

        if ($contactInfo === null) {
            $contactInfo = $this->getContactInformationByDisplaySettings($customerUser);
        }

        return $contactInfo;
    }

    /**
     * @param string $key
     * @param mixed|null $scopeIdentifier
     *
     * @return ContactInfo
     */
    private function createContactInfoFromConfig($key, $scopeIdentifier = null)
    {
        $fullKey = Configuration::getConfigKeyByName($key);
        $text = $this->configManager->get($fullKey, false, false, $scopeIdentifier);

        return $this->contactInfoFactory->createContactInfoWithText($text);
    }

    /**
     * @return ContactInfo
     */
    private function createContactInfoForAnon()
    {
        $contactInfo = $this->createContactInfoFromConfig(Configuration::GUEST_CONTACT_INFO_TEXT);

        return $contactInfo;
    }

    private function isUserConfigurationAllowed(): bool
    {
        $configKey = Configuration::getConfigKeyByName(Configuration::ALLOW_USER_CONFIGURATION);

        return (bool)$this->configManager->get($configKey);
    }

    private function getContactInformationByUserConfiguration(CustomerUserInterface $customerUser = null): ?ContactInfo
    {
        if ($customerUser && $this->isUserConfigurationAllowed()) {
            $owner = $customerUser->getOwner();
            $selectedOption = $this->userContactInfoOptionProvider->getSelectedOption($owner);
            if (!$this->isUserConfigRelatedOption($selectedOption) && $customerUser->getCustomer()) {
                $owner = $customerUser->getCustomer()->getOwner();
                $selectedOption = $this->userContactInfoOptionProvider->getSelectedOption($owner);
            }

            if ($this->isUserConfigRelatedOption($selectedOption)) {
                return $this->createContactInfoByUserAndSelectedOption($owner, $selectedOption);
            }
        }

        return null;
    }

    private function getContactInformationByDisplaySettings(CustomerUserInterface $customerUser = null): ?ContactInfo
    {
        $selectedOption = $this->sourceOptionsProvider->getSelectedOption();
        if (!$customerUser && $selectedOption !== ContactInfoSourceOptionsProvider::DONT_DISPLAY) {
            return $this->createContactInfoForAnon();
        }

        switch ($selectedOption) {
            case ContactInfoSourceOptionsProvider::PRE_CONFIGURED:
                $contactInfo = $this->createContactInfoFromConfig(Configuration::CONTACT_DETAILS);

                break;
            case ContactInfoSourceOptionsProvider::CUSTOMER_OWNER:
                $user = $customerUser->getCustomer()->getOwner();
                $contactInfo = $this->contactInfoFactory->createContactInfoByUser($user);

                break;
            case ContactInfoSourceOptionsProvider::CUSTOMER_USER_OWNER:
                $user = $customerUser->getOwner();
                $contactInfo = $this->contactInfoFactory->createContactInfoByUser($user);

                break;
            case ContactInfoSourceOptionsProvider::DONT_DISPLAY:
            default:
                $contactInfo = $this->contactInfoFactory->createContactInfo();
        }

        return $contactInfo;
    }

    private function createContactInfoByUserAndSelectedOption(User $user, string $selectedOption): ContactInfo
    {
        switch ($selectedOption) {
            case ContactInfoUserOptionsProvider::USE_SYSTEM:
                $contactInfo = $this->createContactInfoFromConfig(Configuration::CONTACT_DETAILS);
                break;
            case UserOptionsProvider::USE_USER_PROFILE_DATA:
                $contactInfo = $this->contactInfoFactory->createContactInfoByUser($user);
                break;
            case UserOptionsProvider::ENTER_MANUALLY:
                $contactInfo = $this->createContactInfoFromConfig(Configuration::CONTACT_INFO_MANUAL_TEXT, $user);
                break;
            case UserOptionsProvider::DON_T_DISPLAY_CONTACT_INFO:
            default:
                $contactInfo = $this->contactInfoFactory->createContactInfo();
        }

        return $contactInfo;
    }

    private function isUserConfigRelatedOption(string $selectedOption): bool
    {
        return \in_array($selectedOption, self::USER_CONFIG_RELATED_OPTIONS, true);
    }
}
