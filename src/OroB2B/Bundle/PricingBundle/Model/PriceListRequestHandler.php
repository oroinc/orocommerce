<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;

/**
 * Get price list by id from request or return default if not found
 */
class PriceListRequestHandler
{
    const PRICE_LIST_KEY = 'priceListId';
    const PRICE_LIST_CURRENCY_KEY = 'priceCurrencies';
    const TIER_PRICES_KEY = 'showTierPrices';

    /** @var Request */
    protected $request;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var ManagerRegistry */
    protected $priceListClass;

    /** @var PriceList */
    protected $defaultPriceList;

    /** @var PriceList[] */
    protected $priceLists = [];

    /**
     * @param ManagerRegistry $registry
     * @param string $priceListClass
     */
    public function __construct(ManagerRegistry $registry, $priceListClass)
    {
        $this->registry = $registry;
        $this->priceListClass = $priceListClass;
    }

    /**
     * @return PriceList
     */
    public function getPriceList()
    {
        if (!$this->request) {
            return $this->getDefaultPriceList();
        }

        $priceListId = (int)$this->request->get(self::PRICE_LIST_KEY);
        if ($priceListId) {
            if (array_key_exists($priceListId, $this->priceLists)) {
                return $this->priceLists[$priceListId];
            }

            $priceList = $this->getPriceListRepository()->find($priceListId);
            if ($priceList) {
                $this->priceLists[$priceListId] = $priceList;

                return $priceList;
            }
        }

        return $this->getDefaultPriceList();
    }

    /**
     * @return string[]
     */
    public function getPriceListSelectedCurrencies()
    {
        $priceListCurrencies = $this->getPriceList()->getCurrencies();

        if (!$this->request) {
            return $priceListCurrencies;
        }

        $currencies = $this->request->get(self::PRICE_LIST_CURRENCY_KEY);
        if (null === $currencies) {
            return $priceListCurrencies;
        }

        if (!is_array($currencies)) {
            return filter_var($currencies, FILTER_VALIDATE_BOOLEAN) ? $priceListCurrencies : [];
        }

        $currencies = array_intersect($priceListCurrencies, $currencies);

        sort($currencies);

        return $currencies;
    }

    /**
     * @return bool
     */
    public function getShowTierPrices()
    {
        if (!$this->request) {
            return false;
        }

        $value = $this->request->get(self::TIER_PRICES_KEY);

        if (is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) $value;
    }

    /**
     * @return PriceList
     */
    protected function getDefaultPriceList()
    {
        if (!$this->defaultPriceList) {
            $this->defaultPriceList = $this->getPriceListRepository()->getDefault();
        }

        if (!$this->defaultPriceList) {
            throw new \InvalidArgumentException('Default PriceList not found');
        }

        return $this->defaultPriceList;
    }

    /**
     * @return PriceListRepository
     */
    protected function getPriceListRepository()
    {
        return $this->registry->getManagerForClass($this->priceListClass)->getRepository($this->priceListClass);
    }

    /**
     * @param Request $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }
}
