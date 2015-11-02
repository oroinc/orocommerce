<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\FrontendBundle\Request\FrontendHelper;
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
     * @var string|null
     */
    protected $backendSystemConfigurationPath;

    /**
     * @var string|null
     */
    protected $frontendSystemConfigurationPath;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

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
     * @param string $scope
     * @return $this
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * @param string|null $backendSystemConfigurationPath
     * @param string|null $frontendSystemConfigurationPath
     * @return $this
     */
    public function setSystemConfigurationPath(
        $backendSystemConfigurationPath = null,
        $frontendSystemConfigurationPath = null
    ) {
        $this->backendSystemConfigurationPath = $backendSystemConfigurationPath;
        $this->frontendSystemConfigurationPath = $frontendSystemConfigurationPath;
    }

    /**
     * @param ProductSelectDBQueryEvent $event
     */
    public function onDBQuery(ProductSelectDBQueryEvent $event)
    {
        if (!$this->isConditionsAcceptable($event)) {
            return;
        }

        if ($this->frontendHelper->isFrontendRequest($this->request)) {
            $inventoryStatuses = $this->configManager->get($this->frontendSystemConfigurationPath);
        } else {
            $inventoryStatuses = $this->configManager->get($this->backendSystemConfigurationPath);
        }

        $this->modifier->modifyByInventoryStatus($event->getQueryBuilder(), $inventoryStatuses);
    }

    /**
     * @param ProductSelectDBQueryEvent $event
     * @return bool
     */
    protected function isConditionsAcceptable(ProductSelectDBQueryEvent $event)
    {
        if (!$this->backendSystemConfigurationPath && !$this->frontendSystemConfigurationPath) {
            throw new \LogicException('SystemConfigurationPath not configured for ProductSelectDBQueryEventListener');
        }

        if (!$this->scope) {
            throw new \LogicException('Scope not configured for ProductSelectDBQueryEventListener');
        }

        if ($event->getDataParameters()->get('scope') !== $this->scope) {
            return false;
        }

        return true;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }
}
