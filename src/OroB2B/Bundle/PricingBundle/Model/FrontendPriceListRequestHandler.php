<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class FrontendPriceListRequestHandler extends AbstractPriceListRequestHandler
{
    const PRICE_LIST_CURRENCY_KEY = 'priceCurrency';

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

        if ($this->request) {
            $currency = $this->request->get(self::PRICE_LIST_CURRENCY_KEY);

            if (in_array($currency, $priceListCurrencies, true)) {
                return [$currency];
            }
        }

        if ($this->session->has(self::PRICE_LIST_CURRENCY_KEY)) {
            $currency = $this->session->get(self::PRICE_LIST_CURRENCY_KEY);

            if (in_array($currency, $priceListCurrencies, true)) {
                return [$currency];
            }
        }

        return (array) reset($priceListCurrencies);
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
