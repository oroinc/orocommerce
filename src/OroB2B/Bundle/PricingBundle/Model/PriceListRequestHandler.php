<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;

class PriceListRequestHandler implements PriceListRequestHandlerInterface
{
    const TIER_PRICES_KEY = 'showTierPrices';
    const WEBSITE_KEY = 'website';
    const PRICE_LIST_CURRENCY_KEY = 'priceCurrencies';
    const SAVE_STATE_KEY = 'saveState';
    const PRICE_LIST_KEY = 'priceListId';
    const ACCOUNT_ID = 'account_id';

    /**
     * @var RequestStack
     */
    protected $requestStack;

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
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string //todo setter injection
     */
    protected $priceListClass = 'OroB2B\Bundle\PricingBundle\Entity\PriceList';

    /**
     * @var PriceList
     */
    protected $defaultPriceList;

    /**
     * @var PriceList[]
     */
    protected $priceLists = [];

    /**
     * @param RequestStack $requestStack
     * @param SessionInterface $session
     * @param SecurityFacade $securityFacade
     * @param PriceListTreeHandler $priceListTreeHandler
     * @param ManagerRegistry $registry
     */
    public function __construct(
        RequestStack $requestStack,
        SessionInterface $session,
        SecurityFacade $securityFacade,
        PriceListTreeHandler $priceListTreeHandler,
        ManagerRegistry $registry
    ) {
        $this->requestStack = $requestStack;
        $this->session = $session;
        $this->securityFacade = $securityFacade;
        $this->priceListTreeHandler = $priceListTreeHandler;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriceListByAccount(Account $account = null, Website $website = null)
    {
        $account = $account ?: $this->getAccount();
        $website = $website ?: $this->getWebsite();
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
        if (null === $currencies) {
            return $priceListCurrencies;
        }

        $currencies = array_intersect($priceListCurrencies, (array)$currencies);

        if ($currencies && $request->get(self::SAVE_STATE_KEY)) {
            $this->session->set(self::PRICE_LIST_CURRENCY_KEY, $currencies);
        }

        if (!$currencies && $this->session->has(self::PRICE_LIST_CURRENCY_KEY)) {
            $currencies = $this->session->get(self::PRICE_LIST_CURRENCY_KEY);
        }

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
        $showTierPrices = $request->get(self::TIER_PRICES_KEY);

        $showTierPrices = null !== $showTierPrices ? filter_var($showTierPrices, FILTER_VALIDATE_BOOLEAN) : null;

        if ($request->get(self::SAVE_STATE_KEY)) {
            $this->session->set(self::TIER_PRICES_KEY, $showTierPrices);
        }

        if (null === $showTierPrices && $this->session->has(self::TIER_PRICES_KEY)) {
            $showTierPrices = $this->session->get(self::TIER_PRICES_KEY);
        }

        return $showTierPrices;
    }


    /**
     * @return null|Account
     */
    protected function getAccount()
    {
        $user = $this->securityFacade->getLoggedUser();

        if ($user instanceof AccountUser) {
            return $user->getAccount();
        } elseif ($user instanceof User) {
            $request = $this->getRequest();
            if ($request && $request->get(self::ACCOUNT_ID)) {
                return $this->registry
                    ->getManagerForClass('OroB2BAccountBundle:Account')
                    ->getRepository('OroB2BAccountBundle:Account')
                    ->find($request->get(self::ACCOUNT_ID));
            }
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
        return $this->registry->getManagerForClass($this->priceListClass)->getRepository($this->priceListClass);
    }

    /**
     * @return null|Website
     */
    protected function getWebsite()
    {
        $website = null;
        $id = $this->getRequest()->get(self::WEBSITE_KEY);
        if ($id) {
            $website = $this->registry->getManagerForClass('OroB2BWebsiteBundle:Website')
                ->getRepository('OroB2BWebsiteBundle:Website')
                ->find($id);
        }
        return $website;
    }

    /**
     * @param string $priceListClass
     */
    public function setPriceListClass($priceListClass)
    {
        $this->priceListClass = $priceListClass;
    }

    /**
     * @return null|\Symfony\Component\HttpFoundation\Request
     */
    protected function getRequest()
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request;
    }
}
