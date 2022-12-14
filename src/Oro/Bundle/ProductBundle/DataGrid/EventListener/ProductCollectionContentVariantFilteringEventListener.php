<?php

namespace Oro\Bundle\ProductBundle\DataGrid\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Handler\RequestContentVariantHandler;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultBefore;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

/**
 * Datagrid preBuild/afterBuild event listener which adds extra parameters and extra criterias to the datagrid and its
 * search datasource query for displaying product collection content variant.
 */
class ProductCollectionContentVariantFilteringEventListener
{
    const CONTENT_VARIANT_ID_CONFIG_PATH = '[options][urlParams][contentVariantId]';
    const VIEW_LINK_PARAMS_CONFIG_PATH = '[properties][view_link][direct_params]';
    const OVERRIDE_VARIANT_CONFIGURATION_CONFIG_PATH = '[options][urlParams][overrideVariantConfiguration]';

    private RequestContentVariantHandler $requestHandler;

    private ManagerRegistry $managerRegistry;

    private ConfigManager $configManager;

    public function __construct(
        RequestContentVariantHandler $requestHandler,
        ManagerRegistry $managerRegistry,
        ConfigManager $configManager
    ) {
        $this->requestHandler = $requestHandler;
        $this->managerRegistry = $managerRegistry;
        $this->configManager = $configManager;
    }

    public function onPreBuild(PreBuild $event)
    {
        $parameters = $event->getParameters();

        $contentVariantId = $this->getContentVariantId($parameters);
        $contentVariant = $this->getProductCollectionContentVariant($contentVariantId);
        if (!$contentVariant) {
            // Skips adding contentVariantId to config and parameters as content variant is not found or
            // not of product collection type.
            return;
        }

        $gridConfig = $event->getConfig();
        $parameters->set(ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY, $contentVariantId);
        $gridConfig->offsetSetByPath(self::CONTENT_VARIANT_ID_CONFIG_PATH, $contentVariantId);

        $overrideVariantConfiguration = $this->isOverrideVariantConfiguration($parameters);
        $parameters->set(
            ProductCollectionContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY,
            $overrideVariantConfiguration
        );
        $gridConfig->offsetSetByPath(
            self::OVERRIDE_VARIANT_CONFIGURATION_CONFIG_PATH,
            (int)$overrideVariantConfiguration
        );
    }

    private function getProductCollectionContentVariant(int $contentVariantId): ?ContentVariantInterface
    {
        if ($contentVariantId) {
            // Method find() is used here because it does not make a query to database if entity is already present
            // in unitOfWork.
            $contentVariant = $this->managerRegistry
                ->getManagerForClass(ContentVariant::class)
                ->find(ContentVariant::class, $contentVariantId);
        }

        return !empty($contentVariant) && $contentVariant->getType() === ProductCollectionContentVariantType::TYPE
            ? $contentVariant
            : null;
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
            ->addWhere(Criteria::expr()->eq(sprintf('integer.assigned_to.variant_%s', $contentVariantId), 1));

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
                    Criteria::expr()->eq(sprintf('integer.manually_added_to.variant_%s', $contentVariantId), 1),
                    Criteria::expr()->eq('integer.is_variant', 0)
                ));
        }
    }

    public function onSearchResultBefore(SearchResultBefore $event)
    {
        // Adds collection sort order info to the product collection datagrid
        $contentVariantId = $event->getDatagrid()->getConfig()->offsetGetByPath(self::CONTENT_VARIANT_ID_CONFIG_PATH);
        if ($contentVariantId) {
            if (!$event->getQuery()->getSortOrder()) {
                $event->getQuery()->setOrderBy(sprintf('decimal.assigned_to_sort_order.variant_%s', $contentVariantId));
            }
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

    private function getContentVariantId(ParameterBag $parameters): int
    {
        $contentVariantId = filter_var(
            $parameters->get(ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY, 0),
            FILTER_VALIDATE_INT
        );

        return $contentVariantId && $contentVariantId > 0
            ? $contentVariantId
            : $this->requestHandler->getContentVariantId();
    }

    private function isOverrideVariantConfiguration(ParameterBag $parameters): ?bool
    {
        if ($parameters->has(ProductCollectionContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY)) {
            $overrideVariantConfiguration = $parameters
                ->get(ProductCollectionContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY);
        } else {
            $overrideVariantConfiguration = $this->requestHandler->getOverrideVariantConfiguration();
        }

        return filter_var($overrideVariantConfiguration, FILTER_VALIDATE_BOOLEAN);
    }
}
