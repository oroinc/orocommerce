<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Prepare ProductPriceScopeCriteria based on user token and request parameters.
 */
class ProductPriceScopeCriteriaRequestHandler
{
    const WEBSITE_KEY = 'websiteId';
    const CUSTOMER_ID_KEY = 'customer_id';
    const PRICE_CONTEXT_ENTITY_KEY = 'price_context_entity';
    const PRICE_CONTEXT_ENTITY_ID_KEY = 'price_context_entity_id';

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
        $searchScope->setContext($this->getContext());

        return $searchScope;
    }

    /**
     * @return null|Customer
     */
    protected function getCustomer()
    {
        $user = $this->getUser();

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
    protected function getWebsite()
    {
        $website = null;

        $user = $this->getUser();
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

    /**
     * @return null|AbstractUser
     */
    protected function getUser()
    {
        $user = $this->tokenAccessor->getUser();
        if (!$user) {
            $token = $this->tokenAccessor->getToken();
            if ($token instanceof AnonymousCustomerUserToken) {
                $visitor = $token->getVisitor();
                if ($visitor) {
                    $user = $visitor->getCustomerUser();
                }
            }
        }

        return $user;
    }

    /**
     * @return null|object
     * @throws \Doctrine\ORM\ORMException
     */
    protected function getContext()
    {
        $request = $this->getRequest();
        if ($request) {
            $class = $request->get(self::PRICE_CONTEXT_ENTITY_KEY);
            $id = $request->get(self::PRICE_CONTEXT_ENTITY_ID_KEY);

            /** @var EntityManager $em */
            $em = $this->registry->getManagerForClass($class);
            if ($em) {
                return $em->getReference($class, $id);
            }
        }

        return null;
    }
}
