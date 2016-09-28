<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;

class ProductDBQueryRestrictionEventListener
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var ProductVisibilityQueryBuilderModifier
     */
    protected $modifier;

    /**
     * @var string|null
     */
    protected $backendSystemConfigurationPath = null;

    /**
     * @var string|null
     */
    protected $frontendSystemConfigurationPath = null;

    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

    /**
     * @var ProductDBQueryRestrictionEvent
     */
    protected $event;

    /**
     * @param ConfigManager $configManager
     * @param ProductVisibilityQueryBuilderModifier $modifier
     * @param FrontendHelper $helper
     */
    public function __construct(
        ConfigManager $configManager,
        ProductVisibilityQueryBuilderModifier $modifier,
        FrontendHelper $helper
    ) {
        $this->configManager = $configManager;
        $this->modifier = $modifier;
        $this->frontendHelper = $helper;
    }

    /**
     * @param string|null $frontendSystemConfigurationPath
     */
    public function setFrontendSystemConfigurationPath($frontendSystemConfigurationPath = null)
    {
        $this->frontendSystemConfigurationPath = $frontendSystemConfigurationPath;
    }

    /**
     * @param string|null $backendSystemConfigurationPath
     */
    public function setBackendSystemConfigurationPath($backendSystemConfigurationPath = null)
    {
        $this->backendSystemConfigurationPath = $backendSystemConfigurationPath;
    }

    /**
     * @param ProductDBQueryRestrictionEvent $event
     */
    public function onDBQuery(ProductDBQueryRestrictionEvent $event)
    {
        $this->event = $event;

        if (!$this->isConditionsAcceptable()) {
            return;
        }

        if ($this->isFrontendRequest() && $this->frontendSystemConfigurationPath) {
            $inventoryStatuses = $this->configManager->get($this->frontendSystemConfigurationPath);
        } elseif (!$this->isFrontendRequest() && $this->backendSystemConfigurationPath) {
            $inventoryStatuses = $this->configManager->get($this->backendSystemConfigurationPath);
        } else {
            return;
        }

        $this->modifier->modifyByInventoryStatus($event->getQueryBuilder(), $inventoryStatuses);
    }

    /**
     * @return bool
     */
    protected function isConditionsAcceptable()
    {
        if (!$this->backendSystemConfigurationPath && !$this->frontendSystemConfigurationPath) {
            throw new \LogicException('SystemConfigurationPath not configured for ProductDBQueryRestrictionEventListener');
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function isFrontendRequest()
    {
        return $this->frontendHelper->isFrontendRequest();
    }
}
