<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;
use OroB2B\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;

class ProductSelectDBQueryEventListener
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
     * @var string
     */
    protected $systemConfigurationPath;

    /**
     * @var ProductSelectDBQueryEvent
     */
    protected $event;

    /**
     * @param ConfigManager $configManager
     * @param ProductVisibilityQueryBuilderModifier $modifier
     */
    public function __construct(ConfigManager $configManager, ProductVisibilityQueryBuilderModifier $modifier)
    {
        $this->configManager = $configManager;
        $this->modifier = $modifier;
    }

    /**
     * @param string $systemConfigurationPath
     * @return $this
     */
    public function setSystemConfigurationPath($systemConfigurationPath)
    {
        $this->systemConfigurationPath = $systemConfigurationPath;
    }

    /**
     * @param ProductSelectDBQueryEvent $event
     */
    public function onDBQuery(ProductSelectDBQueryEvent $event)
    {
        $this->event = $event;

        if (!$this->isConditionsAcceptable()) {
            return;
        }

        $inventoryStatuses = $this->configManager->get($this->systemConfigurationPath);
        $this->modifier->modifyByInventoryStatus($event->getQueryBuilder(), $inventoryStatuses);
    }

    /**
     * @return bool
     */
    protected function isConditionsAcceptable()
    {
        if (!$this->systemConfigurationPath) {
            throw new \LogicException('SystemConfigurationPath not configured for ProductSelectDBQueryEventListener');
        }

        return true;
    }
}
