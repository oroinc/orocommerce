<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides methods to obtain price list by given id, price list currencies,
 * decide whether tier prices should be shown or not
 */
class PriceListRequestHandler implements PriceListRequestHandlerInterface
{
    private RequestStack $requestStack;
    private ManagerRegistry $registry;
    private TokenAccessorInterface $tokenAccessor;

    private ?PriceListRepository $priceListRepository = null;

    /** @var array [organizationId => PriceList, ...] */
    private array $defaultPriceList = [];

    /** @var array [organizationId => [PriceList1, PriceList2, ...], ...] */
    private array $priceLists = [];

    public function __construct(
        RequestStack $requestStack,
        ManagerRegistry $registry,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->requestStack = $requestStack;
        $this->registry = $registry;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritDoc}
     */
    public function getPriceList()
    {
        $organizationId = $this->tokenAccessor->getOrganizationId();

        $priceListId = $this->getPriceListId();
        if (!$priceListId) {
            return $this->getDefaultPriceList();
        }

        if (isset($this->priceLists[$organizationId])
            && array_key_exists($priceListId, $this->priceLists[$organizationId])
        ) {
            return $this->priceLists[$organizationId][$priceListId];
        }

        $priceList = $this->getPriceListRepository()->find($priceListId);
        if ($priceList) {
            $this->priceLists[$organizationId][$priceListId] = $priceList;

            return $priceList;
        }

        return $this->getDefaultPriceList();
    }

    /**
     * {@inheritDoc}
     */
    public function getPriceListSelectedCurrencies(BasePriceList $priceList)
    {
        $priceListCurrencies = $priceList->getCurrencies();

        $request = $this->getRequest();

        if (!$request) {
            return $priceListCurrencies;
        }

        $currencies = $request->get(self::PRICE_LIST_CURRENCY_KEY);

        $session = $request->hasSession() ? $request->getSession() : null;
        if ($session && null === $currencies && $session->has(self::PRICE_LIST_CURRENCY_KEY)) {
            $currencies = $session->get(self::PRICE_LIST_CURRENCY_KEY, []);
        }

        if (filter_var($currencies, FILTER_VALIDATE_BOOLEAN)) {
            return $priceListCurrencies;
        }

        if (null === $currencies) {
            return [];
        }

        $currencies = array_intersect($priceListCurrencies, (array)$currencies);

        sort($currencies);
        return $currencies;
    }

    /**
     * {@inheritDoc}
     */
    public function getShowTierPrices()
    {
        $request = $this->getRequest();
        if (!$request) {
            return false;
        }

        return filter_var($request->get(self::TIER_PRICES_KEY), FILTER_VALIDATE_BOOLEAN);
    }

    private function getDefaultPriceList(): PriceList
    {
        $organizationId = $this->tokenAccessor->getOrganizationId();

        if (!isset($this->defaultPriceList[$organizationId])) {
            $this->defaultPriceList[$organizationId] = $this->getPriceListRepository()
                ->getDefault($this->tokenAccessor->getOrganization());
        }

        if (!isset($this->defaultPriceList[$organizationId])) {
            throw new \InvalidArgumentException('Default PriceList not found');
        }

        return $this->defaultPriceList[$organizationId];
    }

    private function getPriceListId(): int|null
    {
        $request = $this->getRequest();
        if (!$request) {
            return null;
        }

        $value = $request->get(self::PRICE_LIST_KEY);

        if (is_bool($value)) {
            return null;
        }

        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value > 0) {
            return $value;
        }

        return null;
    }

    private function getPriceListRepository(): PriceListRepository
    {
        if (!$this->priceListRepository) {
            $this->priceListRepository = $this->registry->getManagerForClass(PriceList::class)
                ->getRepository(PriceList::class);
        }

        return $this->priceListRepository;
    }

    private function getRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }
}
