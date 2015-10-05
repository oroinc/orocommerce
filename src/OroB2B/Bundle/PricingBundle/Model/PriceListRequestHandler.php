<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;

/**
 * Get price list by id from request or return default if not found
 */
class PriceListRequestHandler extends AbstractPriceListRequestHandler
{
    const PRICE_LIST_KEY = 'priceListId';
    const PRICE_LIST_CURRENCY_KEY = 'priceCurrencies';

    /** @var ManagerRegistry */
    protected $registry;

    /** @var ManagerRegistry */
    protected $priceListClass;

    /** @var PriceList */
    protected $defaultPriceList;

    /** @var PriceList[] */
    protected $priceLists = [];

    /**
     * @param RequestStack $requestStack
     * @param ManagerRegistry $registry
     * @param string $priceListClass
     */
    public function __construct(RequestStack $requestStack, ManagerRegistry $registry, $priceListClass)
    {
        parent::__construct($requestStack);
        $this->registry = $registry;
        $this->priceListClass = $priceListClass;
    }

    /**
     * {@inheritDoc}
     */
    public function getPriceList()
    {
        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return $this->getDefaultPriceList();
        }

        $priceListId = $this->getPriceListId();
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
     * @return int|bool
     */
    public function getPriceListId()
    {
        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return false;
        }

        $value = $request->get(self::PRICE_LIST_KEY);

        if (is_bool($value)) {
            return false;
        }

        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value > 0) {
            return $value;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getPriceListSelectedCurrencies()
    {
        $priceListCurrencies = $this->getPriceList()->getCurrencies();

        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return $priceListCurrencies;
        }

        $currencies = $request->get(self::PRICE_LIST_CURRENCY_KEY);
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
}
