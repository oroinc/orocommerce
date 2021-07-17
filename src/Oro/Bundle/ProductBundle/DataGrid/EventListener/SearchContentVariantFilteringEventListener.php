<?php

namespace Oro\Bundle\ProductBundle\DataGrid\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Handler\RequestContentVariantHandler;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

/**
 * Search event listener for filtering product results
 */
class SearchContentVariantFilteringEventListener
{
    const CONTENT_VARIANT_ID_CONFIG_PATH = '[options][urlParams][contentVariantId]';
    const VIEW_LINK_PARAMS_CONFIG_PATH = '[properties][view_link][direct_params]';
    const OVERRIDE_VARIANT_CONFIGURATION_CONFIG_PATH = '[options][urlParams][overrideVariantConfiguration]';

    /**
     * @var RequestContentVariantHandler $requestHandler
     */
    private $requestHandler;

    /**
     * @var ConfigManager $configManager
     */
    private $configManager;

    public function __construct(RequestContentVariantHandler $requestHandler, ConfigManager $configManager)
    {
        $this->requestHandler = $requestHandler;
        $this->configManager = $configManager;
    }

    public function onPreBuild(PreBuild $event)
    {
        $parameters = $event->getParameters();
        $contentVariantId = $parameters->has(ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY)
            ? $parameters->get(ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY)
            : $this->requestHandler->getContentVariantId();

        $overrideVariantConfiguration = $parameters->has('overrideVariantConfiguration')
            ? $parameters->get('overrideVariantConfiguration')
            : $this->requestHandler->getOverrideVariantConfiguration();

        if (!$contentVariantId) {
            return;
        }

        $event->getConfig()->offsetSetByPath(self::CONTENT_VARIANT_ID_CONFIG_PATH, $contentVariantId);
        $event->getConfig()->offsetSetByPath(
            self::OVERRIDE_VARIANT_CONFIGURATION_CONFIG_PATH,
            (int) $overrideVariantConfiguration
        );
    }

    public function onBuildAfter(BuildAfter $event)
    {
        $datasource = $event->getDatagrid()->getDatasource();

        if (!$datasource instanceof SearchDatasource) {
            return;
        }

        $contentVariantId = $event->getDatagrid()->getConfig()->offsetGetByPath(self::CONTENT_VARIANT_ID_CONFIG_PATH);

        if (!$contentVariantId) {
            return;
        }

        $event->getDatagrid()->getConfig()->offsetAddToArrayByPath(
            self::VIEW_LINK_PARAMS_CONFIG_PATH,
            [
                SluggableUrlGenerator::CONTEXT_TYPE => 'content_variant',
                SluggableUrlGenerator::CONTEXT_DATA => $contentVariantId
            ]
        );

        $datasource
            ->getSearchQuery()
            ->addWhere(Criteria::expr()->eq(sprintf('integer.assigned_to_variant_%s', $contentVariantId), 1));

        $overrideVariantConfiguration = $event->getDatagrid()
            ->getConfig()
            ->offsetGetByPath(self::OVERRIDE_VARIANT_CONFIGURATION_CONFIG_PATH);
        if ($overrideVariantConfiguration) {
            $datasource
                ->getSearchQuery()
                ->addWhere(Criteria::expr()->gte('integer.is_variant', 0));
        } elseif ($this->isVariationsHideCompletely()) {
            $datasource
                ->getSearchQuery()
                ->addWhere(Criteria::expr()->orX(
                    Criteria::expr()->eq(sprintf('integer.manually_added_to_variant_%s', $contentVariantId), 1),
                    Criteria::expr()->eq('integer.is_variant', 0)
                ));
        }
    }

    private function isVariationsHideCompletely(): bool
    {
        $configValue = $this->configManager->get(
            sprintf(
                '%s.%s',
                Configuration::ROOT_NODE,
                Configuration::DISPLAY_SIMPLE_VARIATIONS
            )
        );

        return $configValue !== Configuration::DISPLAY_SIMPLE_VARIATIONS_EVERYWHERE;
    }
}
