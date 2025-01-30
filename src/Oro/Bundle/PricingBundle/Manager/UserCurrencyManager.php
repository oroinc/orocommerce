<?php

namespace Oro\Bundle\PricingBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserSettings;
use Oro\Bundle\PricingBundle\Provider\CurrentCurrencyProviderInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Represents the entry point for the currency settings of the storefront.
 */
class UserCurrencyManager
{
    const SESSION_CURRENCIES = 'currency_by_website';

    public function __construct(
        protected RequestStack $requestStack,
        protected TokenStorageInterface $tokenStorage,
        protected ManagerRegistry $doctrine,
        protected CurrencyProviderInterface $currencyProvider,
        protected WebsiteManager $websiteManager,
        protected CurrentCurrencyProviderInterface $currentCurrencyProvider
    ) {
    }

    /**
     * @param Website|null $website
     * @return string|null
     */
    public function getUserCurrency(?Website $website = null)
    {
        $currency = $this->currentCurrencyProvider->getCurrentCurrency();
        if ($currency) {
            return $this->sanitizeCurrency($currency);
        }

        $website = $this->getWebsite($website);
        $request = $this->requestStack->getCurrentRequest();
        if ($website) {
            $user = $this->getLoggedUser();
            if ($user instanceof CustomerUser) {
                $userSettings = $user->getWebsiteSettings($website);
                if ($userSettings) {
                    $currency = $userSettings->getCurrency();
                }
            } elseif (null !== $request && $request->hasSession() && $request->getSession()->isStarted()) {
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
    public function saveSelectedCurrency($currency, ?Website $website = null)
    {
        $website = $this->getWebsite($website);
        if (!$website) {
            return;
        }

        $user = $this->getLoggedUser();
        $request = $this->requestStack->getCurrentRequest();
        if ($user instanceof CustomerUser) {
            $userWebsiteSettings = $user->getWebsiteSettings($website);
            if (!$userWebsiteSettings) {
                $userWebsiteSettings = new CustomerUserSettings($website);
                $user->setWebsiteSettings($userWebsiteSettings);
            }
            $userWebsiteSettings->setCurrency($currency);
            $this->doctrine->getManagerForClass(CustomerUser::class)->flush();
        } elseif (null !== $request && $request->hasSession() && $request->getSession()->isStarted()) {
            $sessionCurrencies = $this->getSessionCurrencies();
            $sessionCurrencies[$website->getId()] = $currency;
            $request->getSession()->set(self::SESSION_CURRENCIES, $sessionCurrencies);
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
    protected function getWebsite(?Website $website = null)
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
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request || !$request->hasSession()) {
            return [];
        }

        return (array)$request->getSession()->get(self::SESSION_CURRENCIES);
    }
}
