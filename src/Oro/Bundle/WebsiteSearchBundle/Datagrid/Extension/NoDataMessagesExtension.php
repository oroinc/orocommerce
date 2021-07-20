<?php

namespace Oro\Bundle\WebsiteSearchBundle\Datagrid\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\UIBundle\Tools\EntityLabelBuilder;

/**
 * This class decorates `NoDataMessagesExtension` from `OroDatagridBundle`
 * by adding support `WebsiteSearchBundle` functionality
 */
class NoDataMessagesExtension extends AbstractExtension
{
    /**
     * @var AbstractExtension
     */
    private $noDataMessagesExtension;

    /**
     * @var FrontendHelper
     */
    private $frontendHelper;

    /**
     * @var AbstractSearchMappingProvider
     */
    private $searchMappingProvider;

    public function __construct(
        AbstractExtension $noDataMessagesExtension,
        FrontendHelper $frontendHelper,
        AbstractSearchMappingProvider $searchMappingProvider
    ) {
        $this->noDataMessagesExtension = $noDataMessagesExtension;
        $this->frontendHelper = $frontendHelper;
        $this->searchMappingProvider = $searchMappingProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        if ($this->frontendHelper->isFrontendRequest() && SearchDatasource::TYPE === $config->getDatasourceType()) {
            if ($config->offsetExistByPath(DatagridConfiguration::FROM_PATH)) {
                $alias = $config->offsetGetByPath(DatagridConfiguration::FROM_PATH)[0];
                $entityClassName = $this->searchMappingProvider->getEntityClass($alias);

                if ($entityClassName) {
                    $entityHint = EntityLabelBuilder::getEntityPluralLabelTranslationKey($entityClassName);
                    $config->offsetSetByPath(DatagridConfiguration::ENTITY_HINT_PATH, $entityHint);
                }
            }
        }

        $this->noDataMessagesExtension->processConfigs($config);
    }
}
