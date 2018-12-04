<?php

namespace Oro\Bundle\ConsentBundle\Provider;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Single entry point for storing and getting data from the context
 * inside which we are working with consents
 */
class ConsentContextProvider implements ConsentContextProviderInterface
{
    /**
     * @var bool
     */
    private $isInitialized = false;

    /**
     * @var CustomerUser|null
     */
    private $customerUser;

    /**
     * @var Website
     */
    private $website;

    /**
     * @var Scope|null
     */
    private $scope;

    /**
     * @var ScopeManager
     */
    private $scopeManager;

    /**
     * @var SlugRepository
     */
    private $slugRepository;

    /**
     * @var CustomerUserRelationsProvider
     */
    protected $customerUserRelationsProvider;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

    /**
     * @param ScopeManager $scopeManager
     * @param SlugRepository                $slugRepository
     * @param CustomerUserRelationsProvider $customerUserRelationsProvider
     * @param RequestStack                  $requestStack
     * @param FrontendHelper                $frontendHelper
     */
    public function __construct(
        ScopeManager $scopeManager,
        SlugRepository $slugRepository,
        CustomerUserRelationsProvider $customerUserRelationsProvider,
        RequestStack $requestStack,
        FrontendHelper $frontendHelper
    ) {
        $this->scopeManager = $scopeManager;
        $this->slugRepository = $slugRepository;
        $this->customerUserRelationsProvider = $customerUserRelationsProvider;
        $this->requestStack = $requestStack;
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * @param Website           $website
     * @param CustomerUser|null $customerUser
     */
    public function initializeContext(Website $website, CustomerUser $customerUser = null)
    {
        if (!$this->isInitialized) {
            $this->website = $website;
            $this->customerUser = $customerUser && $customerUser->getId() ? $customerUser : null;
            $this->initializeScope();
            $this->isInitialized = true;
        }
    }

    /**
     * Reset all data that was put to the service within context initialization
     */
    public function resetContext()
    {
        $this->isInitialized = false;
        $this->website = null;
        $this->customerUser = null;
        $this->scope = null;
    }

    /**
     * @return bool
     */
    public function isInitialized()
    {
        return $this->isInitialized;
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerUser()
    {
        return $this->customerUser;
    }

    /**
     * {@inheritdoc}
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * {@inheritdoc}
     */
    public function getScope()
    {
        return $this->scope;
    }

    private function initializeScope()
    {
        if ($this->frontendHelper->isFrontendRequest()) {
            $request = $this->requestStack->getCurrentRequest();
            if ($request && $request->attributes->has('_web_content_scope')) {
                $this->scope = $request->attributes->get('_web_content_scope');
            }
        }

        if (!($this->scope instanceof Scope)) {
            $criteria = $this->scopeManager->getCriteria('web_content');
            $criteriaContext = $criteria->toArray();
            $consentContext = $this->getRewrittenWebContentCriteriaContext($criteriaContext);

            $criteria = $this->scopeManager->getCriteria(
                'web_content',
                $consentContext
            );
            $this->scope = $this->slugRepository->findMostSuitableUsedScope($criteria);
        }
    }

    /**
     * @param array $criteriaContext
     *
     * @return array
     */
    private function getRewrittenWebContentCriteriaContext(array $criteriaContext)
    {
        $criteriaContext['website'] = $this->website;
        $criteriaContext['customer'] = $this->customerUser instanceof CustomerUser ?
            $this->customerUser->getCustomer() : null;
        $criteriaContext['customerGroup'] = $this->customerUserRelationsProvider->getCustomerGroup($this->customerUser);

        return $criteriaContext;
    }
}
