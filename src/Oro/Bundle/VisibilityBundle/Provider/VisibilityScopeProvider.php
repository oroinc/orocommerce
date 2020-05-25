<?php

namespace Oro\Bundle\VisibilityBundle\Provider;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Provider\ScopeCustomerCriteriaProvider;
use Oro\Bundle\CustomerBundle\Provider\ScopeCustomerGroupCriteriaProvider;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Component\Website\WebsiteInterface;

/**
 * Provides a way to get product visibility scopes.
 */
class VisibilityScopeProvider
{
    /** @var ScopeManager */
    private $scopeManager;

    /**
     * @param ScopeManager $scopeManager
     */
    public function __construct(ScopeManager $scopeManager)
    {
        $this->scopeManager = $scopeManager;
    }

    /**
     * @param WebsiteInterface $website
     *
     * @return Scope
     */
    public function getProductVisibilityScope(WebsiteInterface $website): Scope
    {
        return $this->findOrCreateScope(ProductVisibility::getScopeType());
    }

    /**
     * @param WebsiteInterface $website
     *
     * @return Scope|null
     */
    public function findProductVisibilityScope(WebsiteInterface $website): ?Scope
    {
        return $this->findScope(ProductVisibility::getScopeType());
    }

    /**
     * @param WebsiteInterface $website
     *
     * @return int|null
     */
    public function findProductVisibilityScopeId(WebsiteInterface $website): ?int
    {
        return $this->findScopeId(ProductVisibility::getScopeType());
    }

    /**
     * @param Customer         $customer
     * @param WebsiteInterface $website
     *
     * @return Scope
     */
    public function getCustomerProductVisibilityScope(
        Customer $customer,
        WebsiteInterface $website
    ): Scope {
        return $this->findOrCreateScope(
            CustomerProductVisibility::getScopeType(),
            [ScopeCustomerCriteriaProvider::CUSTOMER => $customer]
        );
    }

    /**
     * @param Customer         $customer
     * @param WebsiteInterface $website
     *
     * @return Scope|null
     */
    public function findCustomerProductVisibilityScope(
        Customer $customer,
        WebsiteInterface $website
    ): ?Scope {
        return $this->findScope(
            CustomerProductVisibility::getScopeType(),
            [ScopeCustomerCriteriaProvider::CUSTOMER => $customer]
        );
    }

    /**
     * @param Customer         $customer
     * @param WebsiteInterface $website
     *
     * @return int|null
     */
    public function findCustomerProductVisibilityScopeId(
        Customer $customer,
        WebsiteInterface $website
    ): ?int {
        return $this->findScopeId(
            CustomerProductVisibility::getScopeType(),
            [ScopeCustomerCriteriaProvider::CUSTOMER => $customer]
        );
    }

    /**
     * @param CustomerGroup    $customerGroup
     * @param WebsiteInterface $website
     *
     * @return Scope
     */
    public function getCustomerGroupProductVisibilityScope(
        CustomerGroup $customerGroup,
        WebsiteInterface $website
    ): Scope {
        return $this->findOrCreateScope(
            CustomerGroupProductVisibility::getScopeType(),
            [ScopeCustomerGroupCriteriaProvider::CUSTOMER_GROUP => $customerGroup]
        );
    }

    /**
     * @param CustomerGroup    $customerGroup
     * @param WebsiteInterface $website
     *
     * @return Scope|null
     */
    public function findCustomerGroupProductVisibilityScope(
        CustomerGroup $customerGroup,
        WebsiteInterface $website
    ): ?Scope {
        return $this->findScope(
            CustomerGroupProductVisibility::getScopeType(),
            [ScopeCustomerGroupCriteriaProvider::CUSTOMER_GROUP => $customerGroup]
        );
    }

    /**
     * @param CustomerGroup    $customerGroup
     * @param WebsiteInterface $website
     *
     * @return int|null
     */
    public function findCustomerGroupProductVisibilityScopeId(
        CustomerGroup $customerGroup,
        WebsiteInterface $website
    ): ?int {
        return $this->findScopeId(
            CustomerGroupProductVisibility::getScopeType(),
            [ScopeCustomerGroupCriteriaProvider::CUSTOMER_GROUP => $customerGroup]
        );
    }

    /**
     * @param string     $scopeType
     * @param array|null $context
     *
     * @return Scope
     */
    protected function findOrCreateScope(string $scopeType, array $context = null): Scope
    {
        return $this->scopeManager->findOrCreate($scopeType, $context);
    }

    /**
     * @param string     $scopeType
     * @param array|null $context
     *
     * @return Scope|null
     */
    protected function findScope(string $scopeType, array $context = null): ?Scope
    {
        return $this->scopeManager->find($scopeType, $context);
    }

    /**
     * @param string     $scopeType
     * @param array|null $context
     *
     * @return int|null
     */
    protected function findScopeId(string $scopeType, array $context = null): ?int
    {
        return $this->scopeManager->findId($scopeType, $context);
    }
}
