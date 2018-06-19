<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\HttpFoundation\RequestStack;

class ProductPriceScopeCriteriaRequestHandler
{
    const WEBSITE_KEY = 'websiteId';
    const CUSTOMER_ID_KEY = 'customer_id';

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var TokenAccessorInterface
     */
    protected $tokenAccessor;

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
     * @var PriceListRepository
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
     * @param TokenAccessorInterface $tokenAccessor
     * @param ManagerRegistry $registry
     * @param CustomerUserRelationsProvider $relationsProvider
     * @param WebsiteManager $websiteManager
     */
    public function __construct(
        RequestStack $requestStack,
        TokenAccessorInterface $tokenAccessor,
        ManagerRegistry $registry,
        CustomerUserRelationsProvider $relationsProvider,
        WebsiteManager $websiteManager
    ) {
        $this->requestStack = $requestStack;
        $this->tokenAccessor = $tokenAccessor;
        $this->registry = $registry;
        $this->relationsProvider = $relationsProvider;
        $this->websiteManager = $websiteManager;
    }

    /**
     * @return ProductPriceScopeCriteriaInterface
     */
    public function getPriceScopeCriteria(): ProductPriceScopeCriteriaInterface
    {
        $searchScope = new ProductPriceScopeCriteria();
        $searchScope->setCustomer($this->getCustomer());
        $searchScope->setWebsite($this->getWebsite());

        return $searchScope;
    }

    /**
     * @return null|Customer
     */
    public function getCustomer()
    {
        $user = $this->tokenAccessor->getUser();

        if ($user instanceof User) {
            $request = $this->getRequest();
            if ($request && $customerId = $request->get(self::CUSTOMER_ID_KEY)) {
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
     * @return null|Website
     */
    public function getWebsite()
    {
        $website = null;

        $user = $this->tokenAccessor->getUser();
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
