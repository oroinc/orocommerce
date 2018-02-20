<?php

namespace Oro\Bundle\ProductBundle\DataGrid\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\Handler\RequestContentVariantHandler;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

class SearchContentVariantFilteringEventListener
{
    const CONTENT_VARIANT_ID_CONFIG_PATH = '[options][urlParams][contentVariantId]';
    const VIEW_LINK_PARAMS_CONFIG_PATH = '[properties][view_link][direct_params]';

    /**
     * @var RequestContentVariantHandler $requestHandler
     */
    private $requestHandler;

    /**
     * @param RequestContentVariantHandler $requestHandler
     */
    public function __construct(RequestContentVariantHandler $requestHandler)
    {
        $this->requestHandler = $requestHandler;
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event)
    {
        $contentVariantId = $event->getParameters()->has(ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY)
            ? $event->getParameters()->get(ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY)
            : $this->requestHandler->getContentVariantId();

        if (!$contentVariantId) {
            return;
        }

        $event->getConfig()->offsetSetByPath(self::CONTENT_VARIANT_ID_CONFIG_PATH, $contentVariantId);
    }

    /**
     * @param BuildAfter $event
     */
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
    }
}
