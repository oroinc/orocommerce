<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;

/**
 * Class subscribed to oro_website_search.event.restrict_index_entity.product
 * and adds inventory status restriction based on passed website
 */
class RestrictIndexProductsEventListener
{
    protected ConfigManager $configManager;
    protected ProductVisibilityQueryBuilderModifier $modifier;
    protected ?WebsiteContextManager $websiteContextManager = null;
    protected string $configPath;

    public function __construct(
        ConfigManager $configManager,
        ProductVisibilityQueryBuilderModifier $modifier,
        $configPath
    ) {
        $this->configManager = $configManager;
        $this->modifier = $modifier;
        $this->configPath = $configPath;
    }

    public function setWebsiteContextManager(WebsiteContextManager $websiteContextManager)
    {
        $this->websiteContextManager = $websiteContextManager;
    }

    public function onRestrictIndexEntityEvent(RestrictIndexEntityEvent $event)
    {
        $this->modifier->modifyByStatus($event->getQueryBuilder(), [Product::STATUS_ENABLED]);

        $website = null;
        if ($this->websiteContextManager) {
            $website = $this->websiteContextManager->getWebsite($event->getContext());
        }

        $inventoryStatuses = $this->configManager->get(
            $this->configPath,
            false,
            false,
            $website
        );
        $this->modifier->modifyByInventoryStatus($event->getQueryBuilder(), $inventoryStatuses);
    }
}
