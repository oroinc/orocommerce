<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;
use OroB2B\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;
use Symfony\Component\HttpFoundation\ParameterBag;

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
     * @param string $scope
     * @param string $systemConfigurationPath
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
     * @param ProductSelectDBQueryEvent $event
     */
    public function onDBQuery(ProductSelectDBQueryEvent $event)
    {
        $dataParameters = $event->getDataParameters();

        // waiting for event refactoring
        $scope = null;
        if ($dataParameters instanceof ParameterBag) {
            $scope = $dataParameters->get('scope');
        } elseif (is_array($dataParameters) && isset($dataParameters['scope'])) {
            $scope = $dataParameters['scope'];
        }

        if ($scope !== $this->scope) {
            return;
        }

        $inventoryStatuses = $this->configManager->get($this->systemConfigurationPath);
        $this->modifier->modifyByInventoryStatus($event->getQueryBuilder(), $inventoryStatuses);
    }
}
