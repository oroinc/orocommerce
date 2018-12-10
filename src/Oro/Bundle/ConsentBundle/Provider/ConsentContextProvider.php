<?php

namespace Oro\Bundle\ConsentBundle\Provider;

use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;

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
     * @param ScopeManager $scopeManager
     */
    public function __construct(
        ScopeManager $scopeManager
    ) {
        $this->scopeManager = $scopeManager;
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
        return $this->website ?: $this->getScope()->getWebsite();
    }

    /**
     * {@inheritdoc}
     */
    public function getScope()
    {
        return $this->scopeManager->findOrCreate('web_content');
    }
}
