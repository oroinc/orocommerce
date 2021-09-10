<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

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
    private $requestStack;

    /**
     * @var TokenAccessorInterface
     */
    private $tokenAccessor;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var CustomerUserRelationsProvider
     */
    private $relationsProvider;

    /**
     * @var WebsiteManager
     */
    private $websiteManager;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var ProductPriceScopeCriteriaFactoryInterface
     */
    private $priceScopeCriteriaFactory;

    public function __construct(
        RequestStack $requestStack,
        TokenAccessorInterface $tokenAccessor,
        ManagerRegistry $registry,
        CustomerUserRelationsProvider $relationsProvider,
        WebsiteManager $websiteManager,
        AuthorizationCheckerInterface $authorizationChecker,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory
    ) {
        $this->requestStack = $requestStack;
        $this->tokenAccessor = $tokenAccessor;
        $this->registry = $registry;
        $this->relationsProvider = $relationsProvider;
        $this->websiteManager = $websiteManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
    }

    public function getPriceScopeCriteria(): ProductPriceScopeCriteriaInterface
    {
        return $this->priceScopeCriteriaFactory->create(
            $this->getWebsite(),
            $this->getCustomer(),
            $this->getContext()
        );
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

            if ($class && $id) {
                /** @var EntityManager $em */
                $em = $this->registry->getManagerForClass($class);
                if ($em) {
                    $entity = $em->getReference($class, $id);
                    if ($this->authorizationChecker->isGranted('VIEW', $entity)) {
                        return $entity;
                    }
                }
            }
        }

        return null;
    }
}
