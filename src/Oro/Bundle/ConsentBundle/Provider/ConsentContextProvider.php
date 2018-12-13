<?php

namespace Oro\Bundle\ConsentBundle\Provider;

use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * Single entry point for storing and getting data from the context
 * inside which we are working with consents
 */
class ConsentContextProvider implements ConsentContextProviderInterface
{
    /**
     * @var Website
     */
    private $website;

    /**
     * @var ScopeManager
     */
    private $scopeManager;

    /**
     * @var WebsiteManager
     */
    private $websiteManager;

    /**
     * @param ScopeManager $scopeManager
     * @param WebsiteManager $websiteManager
     */
    public function __construct(
        ScopeManager $scopeManager,
        WebsiteManager $websiteManager
    ) {
        $this->scopeManager = $scopeManager;
        $this->websiteManager = $websiteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function setWebsite(Website $website)
    {
        $this->website = $website;
    }

    /**
     * {@inheritdoc}
     */
    public function getWebsite()
    {
        if ($this->website) {
            return $this->website;
        }
        $website = $this->getScope()->getWebsite();

        return $website ?: $this->websiteManager->getCurrentWebsite();
    }

    /**
     * {@inheritdoc}
     */
    public function getScope()
    {
        return $this->scopeManager->findOrCreate('web_content');
    }
}
