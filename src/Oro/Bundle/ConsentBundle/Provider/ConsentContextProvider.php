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

        //Returns current website if it is or default one otherwise (for admin panel)
        return $this->websiteManager->getCurrentWebsite() ?: $this->websiteManager->getDefaultWebsite();
    }

    /**
     * {@inheritdoc}
     */
    public function getScope()
    {
        return $this->scopeManager->findMostSuitable('web_content');
    }
}
