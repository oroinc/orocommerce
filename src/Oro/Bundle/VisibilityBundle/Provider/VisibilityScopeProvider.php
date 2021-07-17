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

    public function __construct(ScopeManager $scopeManager)
    {
        $this->scopeManager = $scopeManager;
    }

    public function getProductVisibilityScope(WebsiteInterface $website): Scope
    {
        return $this->findOrCreateScope(ProductVisibility::getScopeType());
    }

    public function findProductVisibilityScope(WebsiteInterface $website): ?Scope
    {
        return $this->findScope(ProductVisibility::getScopeType());
    }

    public function findProductVisibilityScopeId(WebsiteInterface $website): ?int
    {
        return $this->findScopeId(ProductVisibility::getScopeType());
    }

    public function getCustomerProductVisibilityScope(
        Customer $customer,
        WebsiteInterface $website
    ): Scope {
        return $this->findOrCreateScope(
            CustomerProductVisibility::getScopeType(),
            [ScopeCustomerCriteriaProvider::CUSTOMER => $customer]
        );
    }

    public function findCustomerProductVisibilityScope(
        Customer $customer,
        WebsiteInterface $website
    ): ?Scope {
        return $this->findScope(
            CustomerProductVisibility::getScopeType(),
            [ScopeCustomerCriteriaProvider::CUSTOMER => $customer]
        );
    }

    public function findCustomerProductVisibilityScopeId(
        Customer $customer,
        WebsiteInterface $website
    ): ?int {
        return $this->findScopeId(
            CustomerProductVisibility::getScopeType(),
            [ScopeCustomerCriteriaProvider::CUSTOMER => $customer]
        );
    }

    public function getCustomerGroupProductVisibilityScope(
        CustomerGroup $customerGroup,
        WebsiteInterface $website
    ): Scope {
        return $this->findOrCreateScope(
            CustomerGroupProductVisibility::getScopeType(),
            [ScopeCustomerGroupCriteriaProvider::CUSTOMER_GROUP => $customerGroup]
        );
    }

    public function findCustomerGroupProductVisibilityScope(
        CustomerGroup $customerGroup,
        WebsiteInterface $website
    ): ?Scope {
        return $this->findScope(
            CustomerGroupProductVisibility::getScopeType(),
            [ScopeCustomerGroupCriteriaProvider::CUSTOMER_GROUP => $customerGroup]
        );
    }

    public function findCustomerGroupProductVisibilityScopeId(
        CustomerGroup $customerGroup,
        WebsiteInterface $website
    ): ?int {
        return $this->findScopeId(
            CustomerGroupProductVisibility::getScopeType(),
            [ScopeCustomerGroupCriteriaProvider::CUSTOMER_GROUP => $customerGroup]
        );
    }

    protected function findOrCreateScope(string $scopeType, array $context = null): Scope
    {
        return $this->scopeManager->findOrCreate($scopeType, $context);
    }

    protected function findScope(string $scopeType, array $context = null): ?Scope
    {
        return $this->scopeManager->find($scopeType, $context);
    }

    protected function findScopeId(string $scopeType, array $context = null): ?int
    {
        return $this->scopeManager->findId($scopeType, $context);
    }
}
