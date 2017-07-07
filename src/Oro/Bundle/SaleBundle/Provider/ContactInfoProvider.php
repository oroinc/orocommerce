<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\SaleBundle\Model\ContactInfo;
use Oro\Bundle\SaleBundle\Model\ContactInfoFactoryInterface;
use Oro\Bundle\SaleBundle\Provider\ContactInfoAvailableUserOptionsProvider as UserOptionsProvider;
use Oro\Bundle\UserBundle\Entity\User;

class ContactInfoProvider implements ContactInfoProviderInterface
{
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

    /**
     * @param ConfigManager                    $configManager
     * @param ContactInfoSourceOptionsProvider $sourceOptionsProvider
     * @param ContactInfoUserOptionsProvider   $userContactInfoProvider
     * @param ContactInfoFactoryInterface      $contactInfoFactory
     */
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
     * @param CustomerUser|null $customerUser
     *
     * @return ContactInfo
     */
    public function getContactInfo(CustomerUser $customerUser = null)
    {
        $contactInfo = $this->contactInfoFactory->createContactInfo();
        switch ($this->sourceOptionsProvider->getSelectedOption()) {
            case ContactInfoSourceOptionsProvider::DONT_DISPLAY:
                break;
            case ContactInfoSourceOptionsProvider::PRE_CONFIGURED:
                if ($customerUser) {
                    $contactInfo = $this->createContactInfoFromConfig(Configuration::CONTACT_DETAILS);
                } else {
                    $contactInfo = $this->createContactInfoForAnon();
                }
                break;
            case ContactInfoSourceOptionsProvider::CUSTOMER_OWNER:
                if ($customerUser) {
                    $user = $customerUser->getCustomer()->getOwner();
                    $contactInfo = $this->createContactInfoByUser($user);
                } else {
                    $contactInfo = $this->createContactInfoForAnon();
                }
                break;
            case ContactInfoSourceOptionsProvider::CUSTOMER_USER_OWNER:
                if ($customerUser) {
                    $user = $customerUser->getOwner();
                    $contactInfo = $this->createContactInfoByUser($user);
                } else {
                    $contactInfo = $this->createContactInfoForAnon();
                }
                break;
        }

        return $contactInfo;
    }

    /**
     * @param User $user
     *
     * @return ContactInfo
     */
    private function createContactInfoByUser(User $user)
    {
        $configKey = Configuration::getConfigKeyByName(Configuration::ALLOW_USER_CONFIGURATION);
        $selectedOption = $this->userContactInfoOptionProvider->getDefaultOption();
        $isAllowedUserConfiguration = $this->configManager->get($configKey);
        if ($isAllowedUserConfiguration) {
            $selectedOption = $this->userContactInfoOptionProvider->getSelectedOption($user);
        }
        $contactInfo = $this->contactInfoFactory->createContactInfo();
        switch ($selectedOption) {
            case UserOptionsProvider::DON_T_DISPLAY_CONTACT_INFO:
                break;
            case ContactInfoUserOptionsProvider::USE_SYSTEM:
                $contactInfo = $this->createContactInfoFromConfig(Configuration::CONTACT_DETAILS);
                break;
            case UserOptionsProvider::USE_USER_PROFILE_DATA:
                $contactInfo = $this->contactInfoFactory->createContactInfoByUser($user);
                break;
            case UserOptionsProvider::ENTER_MANUALLY:
                $contactInfo = $this->createContactInfoFromConfig(Configuration::CONTACT_INFO_MANUAL_TEXT, $user);
                break;
        }

        return $contactInfo;
    }

    /**
     * @param string     $key
     * @param mixed|null $scopeIdentifier
     *
     * @return ContactInfo
     */
    private function createContactInfoFromConfig($key, $scopeIdentifier = null)
    {
        $fullKey = Configuration::getConfigKeyByName($key);
        $text = $this->configManager->get($fullKey, false, false, $scopeIdentifier);
        $contactInfo = $this->contactInfoFactory->createContactInfoWithText($text);

        return $contactInfo;
    }

    /**
     * @return ContactInfo
     */
    private function createContactInfoForAnon()
    {
        $contactInfo = $this->createContactInfoFromConfig(Configuration::GUEST_CONTACT_INFO_TEXT);

        return $contactInfo;
    }
}
