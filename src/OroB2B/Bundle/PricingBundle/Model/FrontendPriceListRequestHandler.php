<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;

class FrontendPriceListRequestHandler extends AbstractPriceListRequestHandler
{
    const PRICE_LIST_CURRENCY_KEY = 'priceCurrency';
    const SAVE_STATE_KEY = 'saveState';

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var PriceListTreeHandler
     */
    protected $priceListTreeHandler;

    /**
     * @param SessionInterface $session
     * @param SecurityFacade $securityFacade
     * @param PriceListTreeHandler $priceListTreeHandler
     */
    public function __construct(
        SessionInterface $session,
        SecurityFacade $securityFacade,
        PriceListTreeHandler $priceListTreeHandler
    ) {
        $this->session = $session;
        $this->securityFacade = $securityFacade;
        $this->priceListTreeHandler = $priceListTreeHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function getPriceList()
    {
        $priceList = $this->priceListTreeHandler->getPriceList($this->getAccountUser());

        if (!$priceList) {
            throw new \RuntimeException('PriceList not found');
        }

        return $priceList;
    }

    /**
     * {@inheritDoc}
     */
    public function getPriceListSelectedCurrencies()
    {
        $priceListCurrencies = $this->getPriceList()->getCurrencies();
        $currency = null;

        if ($this->request) {
            $currency = $this->request->get(self::PRICE_LIST_CURRENCY_KEY);
        }

        if (!$currency && $this->session->has(self::PRICE_LIST_CURRENCY_KEY)) {
            $currency = $this->session->get(self::PRICE_LIST_CURRENCY_KEY);
        }

        if (in_array($currency, $priceListCurrencies, true)) {
            if ($this->request && $this->request->get(self::SAVE_STATE_KEY)) {
                $this->session->set(self::PRICE_LIST_CURRENCY_KEY, $currency);
            }

            return [$currency];
        }

        return (array)reset($priceListCurrencies);
    }

    /**
     * @return bool
     */
    public function getShowTierPrices()
    {
        $showTierPrices = parent::getShowTierPrices();

        if ($this->request
            && !$this->request->get(self::TIER_PRICES_KEY)
            && $this->session->has(self::TIER_PRICES_KEY)
        ) {
            $showTierPrices = $this->session->get(self::TIER_PRICES_KEY);
        }

        if ($this->request && $this->request->get(self::SAVE_STATE_KEY)) {
            $this->session->set(self::TIER_PRICES_KEY, $showTierPrices);
        }

        return $showTierPrices;
    }

    /**
     * @return null|AccountUser
     */
    protected function getAccountUser()
    {
        $accountUser = $this->securityFacade->getLoggedUser();

        return $accountUser instanceof AccountUser ? $accountUser : null;
    }
}
