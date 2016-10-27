<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Model\ProductVisibilitySearchQueryModifier;

class ProductSearchQueryRestrictionEventListener
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var ProductVisibilitySearchQueryModifier
     */
    private $modifier;

    /**
     * @var string|null
     */
    private $frontendSystemConfigurationPath;

    /**
     * @var FrontendHelper
     */
    private $frontendHelper;

    /**
     * @param ConfigManager                        $configManager
     * @param ProductVisibilitySearchQueryModifier $modifier
     * @param FrontendHelper                       $helper
     * @param string                               $frontendSystemConfigurationPath
     */
    public function __construct(
        ConfigManager $configManager,
        ProductVisibilitySearchQueryModifier $modifier,
        FrontendHelper $helper,
        $frontendSystemConfigurationPath
    ) {
        $this->configManager                   = $configManager;
        $this->modifier                        = $modifier;
        $this->frontendHelper                  = $helper;
        $this->frontendSystemConfigurationPath = $frontendSystemConfigurationPath;
    }

    /**
     * @param ProductSearchQueryRestrictionEvent $event
     */
    public function onSearchQuery(ProductSearchQueryRestrictionEvent $event)
    {
        if ($this->isFrontendRequest()) {
            $inventoryStatuses = $this->configManager->get($this->frontendSystemConfigurationPath);
            $this->modifier->modifyByInventoryStatus($event->getQuery(), $inventoryStatuses);
        }
    }

    /**
     * @return bool
     */
    private function isFrontendRequest()
    {
        return $this->frontendHelper->isFrontendRequest();
    }
}
