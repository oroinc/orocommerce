<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides methods to obtain price list by given id, price list currencies,
 * decide whether tier prices should be shown or not
 */
class PriceListRequestHandler implements PriceListRequestHandlerInterface
{
    private RequestStack $requestStack;
    private ManagerRegistry $doctrine;
    private AclHelper $aclHelper;

    private ?PriceList $priceList = null;

    /** @var array [PriceList1, PriceList2, ...] */
    private array $priceLists = [];

    public function __construct(
        RequestStack $requestStack,
        ManagerRegistry $doctrine,
        AclHelper $aclHelper
    ) {
        $this->requestStack = $requestStack;
        $this->doctrine = $doctrine;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function getPriceList()
    {
        $priceListId = $this->getPriceListId();
        if (!$priceListId) {
            return $this->getFirstPriceList();
        }

        if (\array_key_exists($priceListId, $this->priceLists)) {
            return $this->priceLists[$priceListId];
        }

        $priceList = $this->getPriceListById($priceListId);
        if ($priceList) {
            $this->priceLists[$priceListId] = $priceList;

            return $priceList;
        }

        return $this->getFirstPriceList();
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

    private function getFirstPriceList(): PriceList
    {
        if (!$this->priceList) {
            $qb = $this->doctrine->getRepository(PriceList::class)
                ->createQueryBuilder('p')
                ->orderBy('p.id')
                ->setMaxResults(1);

            $this->priceList = $this->aclHelper->apply($qb)->getOneOrNullResult();
        }

        if (!isset($this->priceList)) {
            throw new \InvalidArgumentException('PriceList not found');
        }

        return $this->priceList;
    }

    private function getPriceListById(int $id): ?PriceList
    {
        $qb = $this->doctrine->getRepository(PriceList::class)
            ->createQueryBuilder('p')
            ->where('p.id = :id')
            ->setParameter('id', $id);

        return $this->aclHelper->apply($qb)->getOneOrNullResult();
    }

    private function getPriceListId(): ?int
    {
        $request = $this->getRequest();
        if (!$request) {
            return null;
        }

        $value = $request->get(self::PRICE_LIST_KEY);

        if (\is_bool($value)) {
            return null;
        }

        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value > 0) {
            return $value;
        }

        return null;
    }

    private function getRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }
}
