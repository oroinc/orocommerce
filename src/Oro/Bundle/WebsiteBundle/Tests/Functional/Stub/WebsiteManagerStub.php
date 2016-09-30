<?php

namespace Oro\Bundle\WebsiteBundle\Tests\Functional\Stub;

use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

class WebsiteManagerStub extends WebsiteManager
{
    /**
     * @var Website
     */
    protected $defaultWebsite;

    /**
     * @param Website $currentWebsite
     * @param Website $defaultWebsite
     */
    public function __construct(Website $currentWebsite, Website $defaultWebsite)
    {
        $this->currentWebsite = $currentWebsite;
        $this->defaultWebsite = $defaultWebsite;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentWebsite()
    {
        return $this->currentWebsite;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultWebsite()
    {
        return $this->defaultWebsite;
    }
}
