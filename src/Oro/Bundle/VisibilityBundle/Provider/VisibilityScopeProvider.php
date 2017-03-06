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

class VisibilityScopeProvider
{
    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    public function __construct(ScopeManager $scopeManager)
    {
        $this->scopeManager = $scopeManager;
    }

    /**
     * @param WebsiteInterface $website
     * @return Scope
     */
    public function getProductVisibilityScope(WebsiteInterface $website)
    {
        return $this->scopeManager->findOrCreate(ProductVisibility::getScopeType());
    }

    /**
     * @param Customer $customer
     * @param WebsiteInterface $website
     * @return Scope
     */
    public function getCustomerProductVisibilityScope(Customer $customer, WebsiteInterface $website)
    {
        return $this->scopeManager->findOrCreate(
            CustomerProductVisibility::getScopeType(),
            [
                ScopeCustomerCriteriaProvider::ACCOUNT => $customer
            ]
        );
    }

    /**
     * @param CustomerGroup $customerGroup
     * @param WebsiteInterface $website
     * @return Scope
     */
    public function getCustomerGroupProductVisibilityScope(CustomerGroup $customerGroup, WebsiteInterface $website)
    {
        return $this->scopeManager->findOrCreate(
            CustomerGroupProductVisibility::getScopeType(),
            [
                ScopeCustomerGroupCriteriaProvider::FIELD_NAME => $customerGroup
            ]
        );
    }
}
