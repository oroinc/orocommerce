<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use Symfony\Component\HttpFoundation\Session\Session;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PricingBundle\Model\FrontendPriceListRequestHandler;

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
        $currency = $this->session->get(FrontendPriceListRequestHandler::PRICE_LIST_CURRENCY_KEY);

        //@TODO return correct currency. Now we have not default currency value.
        if (!$currency) {
            $currency = self::DEFAULT_CURRENCY;
        }

        return $currency;
    }
}
