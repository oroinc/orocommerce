<?php

namespace Oro\Bundle\WebsiteBundle\Provider;

use Oro\Bundle\ScopeBundle\Manager\AbstractScopeCriteriaProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

class ScopeCriteriaProvider extends AbstractScopeCriteriaProvider
{
    const WEBSITE = 'website';

    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @param WebsiteManager $websiteManager
     */
    public function __construct(WebsiteManager $websiteManager)
    {
        $this->websiteManager = $websiteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteriaForCurrentScope()
    {
        return [static::WEBSITE => $this->websiteManager->getCurrentWebsite()];
    }

    /**
     * @return string
     */
    public function getCriteriaField()
    {
        return self::WEBSITE;
    }

    /**
     * @return string
     */
    protected function getCriteriaValueType()
    {
        return Website::class;
    }
}
