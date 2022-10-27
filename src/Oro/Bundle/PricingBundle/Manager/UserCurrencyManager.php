<?php

namespace Oro\Bundle\PricingBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserSettings;
use Oro\Bundle\PricingBundle\Provider\CurrentCurrencyProviderInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Represents the entry point for the currency settings of the store frontend.
 */
class UserCurrencyManager
{
    const SESSION_CURRENCIES = 'currency_by_website';

    /** @var Session */
    protected $session;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var CurrencyProviderInterface */
    protected $currencyProvider;

    /** @var WebsiteManager */
    protected $websiteManager;

    /** @var CurrentCurrencyProviderInterface */
    protected $currentCurrencyProvider;

    public function __construct(
        Session $session,
        TokenStorageInterface $tokenStorage,
        ManagerRegistry $doctrine,
        CurrencyProviderInterface $currencyProvider,
        WebsiteManager $websiteManager,
        CurrentCurrencyProviderInterface $currentCurrencyProvider
    ) {
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
        $this->doctrine = $doctrine;
        $this->currencyProvider = $currencyProvider;
        $this->websiteManager = $websiteManager;
        $this->currentCurrencyProvider = $currentCurrencyProvider;
    }

    /**
     * @param Website|null $website
     * @return string|null
     */
    public function getUserCurrency(Website $website = null)
    {
        $currency = $this->currentCurrencyProvider->getCurrentCurrency();
        if ($currency) {
            return $this->sanitizeCurrency($currency);
        }

        $website = $this->getWebsite($website);
        if ($website) {
            $user = $this->getLoggedUser();
            if ($user instanceof CustomerUser) {
                $userSettings = $user->getWebsiteSettings($website);
                if ($userSettings) {
                    $currency = $userSettings->getCurrency();
                }
            } elseif ($this->session->isStarted()) {
                $sessionStoredCurrencies = $this->getSessionCurrencies();
                if (array_key_exists($website->getId(), $sessionStoredCurrencies)) {
                    $currency = $sessionStoredCurrencies[$website->getId()];
                }
            }
        }

        return $this->sanitizeCurrency($currency);
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
        if ($user instanceof CustomerUser) {
            $userWebsiteSettings = $user->getWebsiteSettings($website);
            if (!$userWebsiteSettings) {
                $userWebsiteSettings = new CustomerUserSettings($website);
                $user->setWebsiteSettings($userWebsiteSettings);
            }
            $userWebsiteSettings->setCurrency($currency);
            $this->doctrine->getManagerForClass(CustomerUser::class)->flush();
        } elseif ($this->session->isStarted()) {
            $sessionCurrencies = $this->getSessionCurrencies();
            $sessionCurrencies[$website->getId()] = $currency;
            $this->session->set(self::SESSION_CURRENCIES, $sessionCurrencies);
        }
    }

    /**
     * @return array
     */
    public function getAvailableCurrencies()
    {
        return $this->currencyProvider->getCurrencyList();
    }

    /**
     * @return string|null
     */
    public function getDefaultCurrency()
    {
        return $this->currencyProvider->getDefaultCurrency();
    }

    /**
     * @param string|null $currency
     *
     * @return string|null
     */
    protected function sanitizeCurrency($currency)
    {
        if (!$currency || !in_array($currency, $this->getAvailableCurrencies(), true)) {
            $currency = $this->getDefaultCurrency();
        }

        return $currency;
    }

    /**
     * @return null|CustomerUser
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
