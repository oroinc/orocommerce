<?php

namespace Oro\Bundle\ProductBundle\DataGrid\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\ProductBundle\DataGrid\Filter\FrontendInventorySwitcherFilter;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Component\Layout\Extension\Theme\Model\CurrentThemeProvider;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * This class will help the storefront inventory filtering to use a correct filter type.
 */
class FrontendInventoryFilterDatagridListener
{
    private const FILTERS_COLUMNS_INVENTORY_PATH = '[filters][columns][inventory_status]';
    private const FILTERS_COLUMNS_INVENTORY_TYPE_PATH = '[filters][columns][inventory_status][type]';
    private const FILTERS_COLUMNS_INVENTORY_LABEL_PATH = '[filters][columns][inventory_status][label]';

    public function __construct(
        private ConfigManager $configManager,
        private TokenStorageInterface $tokenStorage,
        private CurrentThemeProvider $currentThemeProvider,
        private ThemeManager $themeManager
    ) {
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        $config = $event->getConfig();

        if (!$config->offsetExistByPath(self::FILTERS_COLUMNS_INVENTORY_PATH)) {
            return;
        }

        $this->setInventoryFilterType($config);
        $this->removeFilterForGuests($config);
    }

    private function setInventoryFilterType(DatagridConfiguration $config): void
    {
        if (!$config->offsetExistByPath(self::FILTERS_COLUMNS_INVENTORY_TYPE_PATH)) {
            return;
        }

        $inventoryFilterType = $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::INVENTORY_FILTER_TYPE)
        );

        if (!$inventoryFilterType) {
            return;
        }

        if ($this->isOldTheme()) {
            return;
        }

        $config->offsetSetByPath(self::FILTERS_COLUMNS_INVENTORY_TYPE_PATH, $inventoryFilterType);
        if ($inventoryFilterType === FrontendInventorySwitcherFilter::TYPE) {
            $config->offsetSetByPath(
                self::FILTERS_COLUMNS_INVENTORY_LABEL_PATH,
                'oro.product.frontend.product_inventory_filter.type.inventory-switcher.label'
            );
        }
    }

    private function removeFilterForGuests(DatagridConfiguration $config): void
    {
        if (!$this->isGuest()) {
            return;
        }

        $isEnabledForGuests = (bool) $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::INVENTORY_FILTER_ENABLE_FOR_GUESTS)
        );

        if ($isEnabledForGuests) {
            return;
        }

        $config->removeFilter('inventory_status');
    }

    private function isGuest(): bool
    {
        $token = $this->tokenStorage->getToken();

        return !$token || $token instanceof AnonymousCustomerUserToken;
    }

    private function isOldTheme(): bool
    {
        return $this->themeManager->themeHasParent(
            $this->currentThemeProvider->getCurrentThemeId() ?? '',
            ['default_50', 'default_51', 'default_60',]
        );
    }
}
