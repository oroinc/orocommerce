<?php

namespace Oro\Bundle\VisibilityBundle\Provider;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Provider\ScopeAccountCriteriaProvider;
use Oro\Bundle\CustomerBundle\Provider\ScopeAccountGroupCriteriaProvider;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\WebsiteBundle\Entity\Website;

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
     * @param Website $website
     * @return \Oro\Bundle\ScopeBundle\Entity\Scope
     */
    public function getProductVisibilityScope(Website $website)
    {
        return $this->scopeManager->findOrCreate(ProductVisibility::getScopeType());
    }

    /**
     * @param Customer $account
     * @param Website $website
     * @return \Oro\Bundle\ScopeBundle\Entity\Scope
     */
    public function getAccountProductVisibilityScope(Customer $account, Website $website)
    {
        return $this->scopeManager->findOrCreate(
            AccountProductVisibility::getScopeType(),
            [
                ScopeAccountCriteriaProvider::ACCOUNT => $account
            ]
        );
    }

    /**
     * @param CustomerGroup $accountGroup
     * @param Website $website
     * @return \Oro\Bundle\ScopeBundle\Entity\Scope
     */
    public function getAccountGroupProductVisibilityScope(CustomerGroup $accountGroup, Website $website)
    {
        return $this->scopeManager->findOrCreate(
            AccountGroupProductVisibility::getScopeType(),
            [
                ScopeAccountGroupCriteriaProvider::FIELD_NAME => $accountGroup
            ]
        );
    }
}
