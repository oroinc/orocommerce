<?php

namespace Oro\Bundle\ProductBundle\EventListener\Datagrid\FrontendProduct;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

/**
 * @deprecated since 1.6 will be removed after 1.6.
 * Use {@see Oro\Bundle\ProductBundle\EventListener\Visibility\Restrictions\RestrictProductVariationsEventListener}
 * instead.
 */
class DisplayProductVariationsListener
{
    /** @var ConfigManager */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        /** @var SearchDatasource $datasource */
        $datasource = $event->getDatagrid()->getDatasource();
        $searchQuery = $datasource->getSearchQuery();

        $displaySimpleVariations = $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::DISPLAY_SIMPLE_VARIATIONS));

        if ($displaySimpleVariations === Configuration::DISPLAY_SIMPLE_VARIATIONS_HIDE_COMPLETELY) {
            $searchQuery->addWhere(Criteria::expr()->eq('integer.is_variant', 0));
        }
    }
}
