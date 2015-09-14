<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

abstract class AbstractPriceListRequestHandler
{
    const TIER_PRICES_KEY = 'showTierPrices';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @return PriceList
     */
    abstract public function getPriceList();

    /**
     * @return string[]
     */
    abstract public function getPriceListSelectedCurrencies();

    /**
     * @return bool
     */
    public function getShowTierPrices()
    {
        if (!$this->request) {
            return false;
        }

        return filter_var($this->request->get(self::TIER_PRICES_KEY), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param Request $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }
}
