<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Provider\AccountUserRelationsProvider;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;

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
     * @var string
     */
    protected $priceListClass = 'Oro\Bundle\PricingBundle\Entity\PriceList';

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
     * @var AccountUserRelationsProvider
     */
    protected $relationsProvider;

    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @param RequestStack $requestStack
     * @param SecurityFacade $securityFacade
     * @param PriceListTreeHandler $priceListTreeHandler
     * @param ManagerRegistry $registry
     * @param AccountUserRelationsProvider $relationsProvider
     * @param FrontendHelper $frontendHelper
     */
    public function __construct(
        RequestStack $requestStack,
        SecurityFacade $securityFacade,
        PriceListTreeHandler $priceListTreeHandler,
        ManagerRegistry $registry,
        AccountUserRelationsProvider $relationsProvider,
        FrontendHelper $frontendHelper,
        WebsiteManager $websiteManager
    ) {
        $this->requestStack = $requestStack;
        $this->securityFacade = $securityFacade;
        $this->priceListTreeHandler = $priceListTreeHandler;
        $this->registry = $registry;
        $this->relationsProvider = $relationsProvider;
        $this->frontendHelper = $frontendHelper;
        $this->websiteManager = $websiteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriceListByAccount()
    {
        $website = $this->getWebsite();
        $account = $this->getAccount();
        $priceList = $this->priceListTreeHandler->getPriceList($account, $website);

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
     * @param string $priceListClass
     */
    public function setPriceListClass($priceListClass)
    {
        $this->priceListClass = $priceListClass;
    }

    /**
     * @return null|Account
     */
    protected function getAccount()
    {
        $user = $this->securityFacade->getLoggedUser();

        if ($user instanceof User) {
            $request = $this->getRequest();
            if ($request && $accountId = $request->get(self::ACCOUNT_ID_KEY)) {
                return $this->registry
                    ->getManagerForClass('Oro\Bundle\AccountBundle\Entity\Account')
                    ->getRepository('Oro\Bundle\AccountBundle\Entity\Account')
                    ->find($accountId);
            }
        } else {
            return $this->relationsProvider->getAccountIncludingEmpty($user);
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
                ->getManagerForClass($this->priceListClass)
                ->getRepository($this->priceListClass);
        }
        return $this->priceListRepository;
    }

    /**
     * @return null|Website
     */
    protected function getWebsite()
    {
        $website = null;
        $request = $this->getRequest();

        if ($request) {
            if ($this->frontendHelper->isFrontendRequest($request)) {
                if ($request->attributes->has('current_website')) {
                    $website = $request->attributes->get('current_website');
                }
            } else {
                if ($id = $this->getRequest()->get(self::WEBSITE_KEY)) {
                    $website = $this->registry->getManagerForClass(Website::class)
                        ->getRepository(Website::class)
                        ->find($id);
                } else {
                    $website = $this->websiteManager->getDefaultWebsite();
                }
            }
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
