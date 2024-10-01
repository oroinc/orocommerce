<?php

namespace Oro\Bundle\ProductBundle\DataGrid\Filter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Provider\DictionaryEntityDataProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\ProductBundle\DataGrid\Form\Type\FrontendInventorySwitcherFilterType;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\WebsiteSearchBundle\Datagrid\Filter\SearchMultiEnumFilter;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * The filter by a predefined inventory statuses for a datasource based on a search index.
 */
class FrontendInventorySwitcherFilter extends SearchMultiEnumFilter
{
    public const TYPE = 'inventory-switcher';

    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        DictionaryEntityDataProvider $dictionaryEntityDataProvider,
        private ConfigManager $configManager
    ) {
        parent::__construct($factory, $util, $dictionaryEntityDataProvider);
    }

    #[\Override]
    public function init($name, array $params): void
    {
        parent::init($name, $params);

        $this->params['contextSearch'] = false;
        $this->params[FilterUtility::FRONTEND_TYPE_KEY] = static::TYPE;
        $this->params['inStockStatuses'] = $this->configManager->get(
            Configuration::getConfigKeyByName(
                Configuration::INVENTORY_FILTER_IN_STOCK_STATUSES_FOR_SIMPLE_FILTER
            )
        );
    }

    #[\Override]
    protected function applyRestrictions(FilterDatasourceAdapterInterface $ds, array $data): bool
    {
        if (in_array(FrontendInventorySwitcherFilterType::TYPE_ENABLED, $data['value'])) {
            $inStockStatuses = $this->configManager->get(
                Configuration::getConfigKeyByName(
                    Configuration::INVENTORY_FILTER_IN_STOCK_STATUSES_FOR_SIMPLE_FILTER
                )
            );

            $data['value'] = ExtendHelper::mapToEnumInternalIds($inStockStatuses);
        }

        return parent::applyRestrictions($ds, $data);
    }

    #[\Override]
    protected function getFormType(): string
    {
        return FrontendInventorySwitcherFilterType::class;
    }
}
