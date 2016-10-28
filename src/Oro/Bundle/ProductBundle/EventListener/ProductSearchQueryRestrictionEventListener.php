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
    protected $configManager;

    /**
     * @var ProductVisibilitySearchQueryModifier
     */
    protected $modifier;

    /**
     * @var string|null
     */
    protected $frontendSystemConfigurationPath;

    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

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
        if (!$this->isConditionsAcceptable()) {
            return;
        }

        $inventoryStatuses = $this->configManager->get($this->frontendSystemConfigurationPath);
        $this->modifier->modifyByInventoryStatus($event->getQuery(), $inventoryStatuses);
    }

    /**
     * @param string $frontendSystemConfigurationPath
     * @return $this
     */
    public function setFrontendSystemConfigurationPath($frontendSystemConfigurationPath)
    {
        $this->frontendSystemConfigurationPath = $frontendSystemConfigurationPath;
        
        return $this;
    }
    
    /**
     * @return bool
     */
    protected function isConditionsAcceptable()
    {
        return $this->frontendHelper->isFrontendRequest();
    }
}
