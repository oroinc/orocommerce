<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\HttpFoundation\RequestStack;

class PriceListRequestHandler implements PriceListRequestHandlerInterface
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var PriceListTreeHandler
     */
    protected $priceListTreeHandler;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var PriceList
     */
    protected $defaultPriceList;

    /**
     * @var PriceList[]
     */
    protected $priceLists = [];

    /**
     * @var EntityRepository
     */
    protected $priceListRepository;

    /**
     * @var CustomerUserRelationsProvider
     */
    protected $relationsProvider;

    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @param RequestStack $requestStack
     * @param SecurityFacade $securityFacade
     * @param PriceListTreeHandler $priceListTreeHandler
     * @param ManagerRegistry $registry
     * @param CustomerUserRelationsProvider $relationsProvider
     * @param WebsiteManager $websiteManager
     */
    public function __construct(
        RequestStack $requestStack,
        SecurityFacade $securityFacade,
        PriceListTreeHandler $priceListTreeHandler,
        ManagerRegistry $registry,
        CustomerUserRelationsProvider $relationsProvider,
        WebsiteManager $websiteManager
    ) {
        $this->requestStack = $requestStack;
        $this->securityFacade = $securityFacade;
        $this->priceListTreeHandler = $priceListTreeHandler;
        $this->registry = $registry;
        $this->relationsProvider = $relationsProvider;
        $this->websiteManager = $websiteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriceListByCustomer()
    {
        $website = $this->getWebsite();
        $customer = $this->getCustomer();
        $priceList = $this->priceListTreeHandler->getPriceList($customer, $website);

        if (!$priceList) {
            throw new \RuntimeException('PriceList not found');
        }

        return $priceList;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriceList()
    {
        $priceListId = $this->getPriceListId();
        if (!$priceListId) {
            return $this->getDefaultPriceList();
        }

        if (array_key_exists($priceListId, $this->priceLists)) {
            return $this->priceLists[$priceListId];
        }

        $priceList = $this->getPriceListRepository()->find($priceListId);
        if ($priceList) {
            $this->priceLists[$priceListId] = $priceList;

            return $priceList;
        }

        return $this->getDefaultPriceList();
    }

    /**
     * {@inheritdoc}
     */
    public function getPriceListSelectedCurrencies(BasePriceList $priceList)
    {
        $priceListCurrencies = $priceList->getCurrencies();

        $request = $this->getRequest();

        if (!$request) {
            return $priceListCurrencies;
        }

        $currencies = $request->get(self::PRICE_LIST_CURRENCY_KEY);

        $session = $request->getSession();
        if ($session && null === $currencies && $session->has(self::PRICE_LIST_CURRENCY_KEY)) {
            $currencies = (array)$session->get(self::PRICE_LIST_CURRENCY_KEY);
        }

        if (null === $currencies || filter_var($currencies, FILTER_VALIDATE_BOOLEAN)) {
            return $priceListCurrencies;
        }

        $currencies = array_intersect($priceListCurrencies, (array)$currencies);

        sort($currencies);
        return $currencies;
    }

    /**
     * {@inheritdoc}
     */
    public function getShowTierPrices()
    {
        $request = $this->getRequest();
        if (!$request) {
            return false;
        }

        return filter_var($request->get(self::TIER_PRICES_KEY), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return null|Customer
     */
    protected function getCustomer()
    {
        $user = $this->securityFacade->getLoggedUser();

        if ($user instanceof User) {
            $request = $this->getRequest();
            if ($request && $customerId = $request->get(self::ACCOUNT_ID_KEY)) {
                return $this->registry
                    ->getManagerForClass(Customer::class)
                    ->getRepository(Customer::class)
                    ->find($customerId);
            }
        } else {
            return $this->relationsProvider->getCustomerIncludingEmpty($user);
        }

        return null;
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
     * @return int|null
     */
    protected function getPriceListId()
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

    /**
     * @return PriceListRepository
     */
    protected function getPriceListRepository()
    {
        if (!$this->priceListRepository) {
            $this->priceListRepository = $this->registry
                ->getManagerForClass(PriceList::class)
                ->getRepository(PriceList::class);
        }

        return $this->priceListRepository;
    }

    /**
     * @return null|Website
     */
    protected function getWebsite()
    {
        $website = null;

        $user = $this->securityFacade->getLoggedUser();
        if ($user instanceof User) {
            $request = $this->getRequest();
            if ($request && $id = $request->get(self::WEBSITE_KEY)) {
                $website = $this->registry->getManagerForClass(Website::class)
                    ->getRepository(Website::class)
                    ->find($id);
            } else {
                $website = $this->websiteManager->getDefaultWebsite();
            }
        } else {
            $website = $this->websiteManager->getCurrentWebsite();
        }

        return $website;
    }

    /**
     * @return null|\Symfony\Component\HttpFoundation\Request
     */
    protected function getRequest()
    {
        return $this->requestStack->getCurrentRequest();
    }
}
