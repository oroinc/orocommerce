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
    protected WebsiteContextManager $websiteContextManager;
    protected string $configPath;

    public function __construct(
        ConfigManager $configManager,
        ProductVisibilityQueryBuilderModifier $modifier,
        WebsiteContextManager $websiteContextManager,
        string $configPath
    ) {
        $this->configManager = $configManager;
        $this->modifier = $modifier;
        $this->websiteContextManager = $websiteContextManager;
        $this->configPath = $configPath;
    }

    public function onRestrictIndexEntityEvent(RestrictIndexEntityEvent $event)
    {
        $this->modifier->modifyByStatus($event->getQueryBuilder(), [Product::STATUS_ENABLED]);
        $inventoryStatuses = $this->configManager->get(
            $this->configPath,
            false,
            false,
            $this->websiteContextManager->getWebsite($event->getContext())
        );
        $this->modifier->modifyByInventoryStatus($event->getQueryBuilder(), $inventoryStatuses);
    }
}
