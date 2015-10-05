<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

abstract class AbstractPriceListRequestHandler
{
    const TIER_PRICES_KEY = 'showTierPrices';

    /**
     * @var RequestStack
     */
    protected $requestStack;


    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

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
        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return false;
        }

        return filter_var($request->get(self::TIER_PRICES_KEY), FILTER_VALIDATE_BOOLEAN);
    }
}
