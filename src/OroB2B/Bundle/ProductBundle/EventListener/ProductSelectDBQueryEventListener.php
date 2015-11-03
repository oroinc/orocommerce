<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;

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
    protected $backendSystemConfigurationPath = null;

    /**
     * @var string|null
     */
    protected $frontendSystemConfigurationPath = null;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var FrontendHelper
     */
    protected $frontendHelper;

    /**
     * @param ConfigManager $configManager
     * @param ProductVisibilityQueryBuilderModifier $modifier
     * @param FrontendHelper $helper
     * @param RequestStack $requestStack
     */
    public function __construct(
        ConfigManager $configManager,
        ProductVisibilityQueryBuilderModifier $modifier,
        FrontendHelper $helper,
        RequestStack $requestStack
    )
    {
        $this->configManager = $configManager;
        $this->modifier = $modifier;
        $this->frontendHelper = $helper;
        $this->requestStack = $requestStack;
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
     * @param string|null $frontendSystemConfigurationPath
     * @return $this
     */
    public function setFrontendSystemConfigurationPath($frontendSystemConfigurationPath = null)
    {
        $this->frontendSystemConfigurationPath = $frontendSystemConfigurationPath;
    }

    /**
     * @param string|null $backendSystemConfigurationPath
     * @return $this
     */
    public function setBackendSystemConfigurationPath($backendSystemConfigurationPath = null)
    {
        $this->backendSystemConfigurationPath = $backendSystemConfigurationPath;
    }

    /**
     * @param ProductSelectDBQueryEvent $event
     */
    public function onDBQuery(ProductSelectDBQueryEvent $event)
    {
        if (!$this->isConditionsAcceptable($event)) {
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

        return $event->getDataParameters()->get('scope') === $this->scope;
    }

    /**
     * @return bool
     */
    protected function isFrontendRequest()
    {
        return $this->frontendHelper->isFrontendRequest($this->requestStack->getCurrentRequest());
    }
}
