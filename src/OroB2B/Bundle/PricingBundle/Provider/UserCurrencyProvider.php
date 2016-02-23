<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use Symfony\Component\HttpFoundation\Session\Session;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;

class UserCurrencyProvider
{
    const DEFAULT_CURRENCY = 'USD';

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @return string
     */
    public function getUserCurrency()
    {
        $currencies = $this->session->get(PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY);

        //@TODO return correct currency. Now we have not default currency value.
        if (empty($currency)) {
            $currency = self::DEFAULT_CURRENCY;
        } else {
            $currency = reset($currencies);
        }

        return $currency;
    }
}
