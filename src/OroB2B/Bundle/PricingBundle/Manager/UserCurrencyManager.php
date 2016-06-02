<?php

namespace OroB2B\Bundle\PricingBundle\Manager;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserSettings;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;

class UserCurrencyManager
{
    const SESSION_CURRENCIES = 'currency_by_website';

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var BaseUserManager
     */
    protected $userManager;

    /**
     * @param Session $session
     * @param TokenStorageInterface $tokenStorage
     * @param ConfigManager $configManager
     * @param WebsiteManager $websiteManager
     * @param BaseUserManager $userManager
     */
    public function __construct(
        Session $session,
        TokenStorageInterface $tokenStorage,
        ConfigManager $configManager,
        WebsiteManager $websiteManager,
        BaseUserManager $userManager
    ) {
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
        $this->configManager = $configManager;
        $this->websiteManager = $websiteManager;
        $this->userManager = $userManager;
    }

    /**
     * @param Website|null $website
     * @return string|null
     */
    public function getUserCurrency(Website $website = null)
    {
        $currency = null;
        $website = $this->getWebsite($website);

        if (!$website) {
            return null;
        }

        $user = $this->getLoggedUser();
        if ($user instanceof AccountUser) {
            $userSettings = $user->getWebsiteSettings($website);
            if ($userSettings) {
                $currency = $userSettings->getCurrency();
            }
        } else {
            $sessionStoredCurrencies = $this->getSessionCurrencies();
            if (array_key_exists($website->getId(), $sessionStoredCurrencies)) {
                $currency = $sessionStoredCurrencies[$website->getId()];
            }
        }

        $allowedCurrencies = $this->getAvailableCurrencies();
        if (!$currency || !in_array($currency, $allowedCurrencies, true)) {
            $currency = $this->getDefaultCurrency();
        }

        return $currency;
    }

    /**
     * @param string $currency
     * @param Website|null $website
     */
    public function saveSelectedCurrency($currency, Website $website = null)
    {
        $website = $this->getWebsite($website);
        if (!$website) {
            return;
        }

        $user = $this->getLoggedUser();
        if ($user instanceof AccountUser) {
            $userWebsiteSettings = $user->getWebsiteSettings($website);
            if (!$userWebsiteSettings) {
                $userWebsiteSettings = new AccountUserSettings($website);
                $user->setWebsiteSettings($userWebsiteSettings);
            }
            $userWebsiteSettings->setCurrency($currency);
            $this->userManager->getStorageManager()->flush();
        } else {
            $sessionCurrencies = $this->getSessionCurrencies();
            if (!in_array($currency, $sessionCurrencies, true)) {
                $sessionCurrencies[$website->getId()] = $currency;
                $this->session->set(self::SESSION_CURRENCIES, $sessionCurrencies);
            }
        }
    }

    /**
     * @return array
     */
    public function getAvailableCurrencies()
    {
        return (array)$this->configManager->get('oro_b2b_pricing.enabled_currencies', []);
    }

    /**
     * @return string|null
     */
    public function getDefaultCurrency()
    {
        return $this->configManager->get('oro_b2b_pricing.default_currency');
    }

    /**
     * @return null|AccountUser
     */
    protected function getLoggedUser()
    {
        $user = null;
        $token = $this->tokenStorage->getToken();
        if ($token) {
            $user = $token->getUser();
        }

        return $user;
    }

    /**
     * @param Website|null $website
     * @return Website|null
     */
    protected function getWebsite(Website $website = null)
    {
        if (!$website) {
            $website = $this->websiteManager->getCurrentWebsite();
        }

        return $website;
    }

    /**
     * @return array
     */
    protected function getSessionCurrencies()
    {
        return (array)$this->session->get(self::SESSION_CURRENCIES);
    }
}
