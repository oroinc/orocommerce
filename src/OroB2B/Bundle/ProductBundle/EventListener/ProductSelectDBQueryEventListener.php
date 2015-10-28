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
    protected $scope;

    /**
     * @var string
     */
    protected $systemConfigurationPath;

    /**
     * @param ConfigManager $configManager
     * @param ProductVisibilityQueryBuilderModifier $modifier
     * @param $scope
     * @param $systemConfigurationPath
     */
    public function __construct(
        ConfigManager $configManager,
        ProductVisibilityQueryBuilderModifier $modifier,
        $scope,
        $systemConfigurationPath
    ) {
        $this->configManager = $configManager;
        $this->modifier = $modifier;
        $this->scope = $scope;
        $this->systemConfigurationPath = $systemConfigurationPath;
    }

    /**
     * @param $event
     */
    public function onDBQuery(ProductSelectDBQueryEvent $event)
    {
        $dataParameters = $event->getDataParameters();
        if ($dataParameters->get('scope') !== $this->scope) {
            return;
        }

        $inventoryStatuses = $this->configManager->get($this->systemConfigurationPath);
        $this->modifier->modifyByInventoryStatus($event->getQueryBuilder(), $inventoryStatuses);
    }
}
