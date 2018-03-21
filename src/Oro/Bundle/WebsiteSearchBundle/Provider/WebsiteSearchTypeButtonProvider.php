<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\WebsiteSearchBundle\QueryString\QueryStringProvider;

/**
 * This class provides data needed for website_search_type_button widget
 */
class WebsiteSearchTypeButtonProvider
{
    /** @var WebsiteSearchTypeChainProvider */
    protected $chainProvider;

    /** @var WebsiteManager */
    protected $websiteManager;

    /** @var ConfigManager */
    protected $configManager;

    /** @var QueryStringProvider */
    protected $queryStringProvider;

    /**
     * @param WebsiteManager                 $websiteManager
     * @param ConfigManager                  $configManager
     * @param QueryStringProvider            $queryStringProvider
     * @param WebsiteSearchTypeChainProvider $chainProvider
     */
    public function __construct(
        WebsiteManager $websiteManager,
        ConfigManager $configManager,
        QueryStringProvider $queryStringProvider,
        WebsiteSearchTypeChainProvider $chainProvider
    ) {
        $this->websiteManager      = $websiteManager;
        $this->configManager       = $configManager;
        $this->queryStringProvider = $queryStringProvider;
        $this->chainProvider       = $chainProvider;
    }

    /**
     * @return bool
     */
    public function isWidgetVisible(): bool
    {
        return \count($this->getAllAvailableSearchTypes())>1;
    }

    /**
     * @return array|WebsiteSearchTypeInterface[]
     */
    public function getAllAvailableSearchTypes(): array
    {
        return $this->chainProvider->getSearchTypes();
    }

    /**
     * @return Website|null
     */
    protected function getCurrentWebsite(): ?Website
    {
        return $this->websiteManager->getCurrentWebsite();
    }
}
