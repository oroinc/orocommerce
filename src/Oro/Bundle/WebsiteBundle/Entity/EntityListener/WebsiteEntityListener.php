<?php

namespace Oro\Bundle\WebsiteBundle\Entity\EntityListener;

use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class WebsiteEntityListener
{
    /**
     * @var ScopeManager
     */
    private $scopeManager;

    /**
     * @param ScopeManager $scopeManager
     */
    public function __construct(ScopeManager $scopeManager)
    {
        $this->scopeManager = $scopeManager;
    }

    /**
     * @param Website $website
     */
    public function prePersist(Website $website)
    {
        $criteria = $this->scopeManager->getCriteria(ScopeManager::BASE_SCOPE, ['website' => $website]);
        $this->scopeManager->createScopeByCriteria($criteria, false);
    }
}
