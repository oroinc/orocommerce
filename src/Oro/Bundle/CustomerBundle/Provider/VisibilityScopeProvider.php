<?php

namespace Oro\Bundle\CustomerBundle\Provider;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
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
    private $scopeManager;

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
        return $this->scopeManager->find(ProductVisibility::getScopeType());
    }

    /**
     * @param Account $account
     * @param Website $website
     * @return \Oro\Bundle\ScopeBundle\Entity\Scope
     */
    public function getAccountProductVisibilityScope(Account $account, Website $website)
    {
        return $this->scopeManager->find(
            AccountProductVisibility::getScopeType(),
            [
                ScopeAccountCriteriaProvider::ACCOUNT => $account
            ]
        );
    }

    /**
     * @param AccountGroup $accountGroup
     * @param Website $website
     * @return \Oro\Bundle\ScopeBundle\Entity\Scope
     */
    public function getAccountGroupProductVisibilityScope(AccountGroup $accountGroup, Website $website)
    {
        return $this->scopeManager->find(
            AccountGroupProductVisibility::getScopeType(),
            [
                ScopeAccountGroupCriteriaProvider::FIELD_NAME => $accountGroup
            ]
        );
    }
}
