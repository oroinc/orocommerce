<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;

class SingleUnitModeDataGridListener
{
    const TEMPLATE_TYPE = 'twig';

    /**
     * @var string
     */
    private $unitColumnName;

    /**
     * @var string
     */
    private $quantityColumnName;

    /**
     * @var string
     */
    private $quantityTemplate;

    /**
     * @var array
     */
    private $quantityTemplateContext;

    /**
     * @var SingleUnitModeService
     */
    private $singleUnitModeService;

    /**
     * SingleUnitModeDataGridListener constructor.
     *
     * @param string $unitColumnName
     * @param string $quantityColumnName
     * @param string $quantityTemplate
     * @param array $quantityTemplateContext
     * @param SingleUnitModeService $singleUnitModeService
     */
    public function __construct(
        $unitColumnName,
        $quantityColumnName,
        $quantityTemplate,
        array $quantityTemplateContext,
        SingleUnitModeService $singleUnitModeService
    ) {
        $this->unitColumnName = $unitColumnName;
        $this->quantityColumnName = $quantityColumnName;
        $this->quantityTemplate = $quantityTemplate;
        $this->quantityTemplateContext = $quantityTemplateContext;
        $this->singleUnitModeService = $singleUnitModeService;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        if (!$this->singleUnitModeService->isSingleUnitMode()) {
            return;
        }

        $config = $event->getDatagrid()->getConfig();

        $config->removeColumn($this->unitColumnName);

        if (!$this->singleUnitModeService->isSingleUnitModeCodeVisible()) {
            return;
        }

        $this->addUnitLabelToQuantityColumn($config);
    }

    /**
     * @param DatagridConfiguration $configuration
     */
    private function addUnitLabelToQuantityColumn(DatagridConfiguration $configuration)
    {
        $columnNamesContext = [
            'quantityColumnName' => $this->quantityColumnName,
            'unitColumnName' => $this->unitColumnName,
        ];

        $templateContext = array_merge($columnNamesContext, $this->quantityTemplateContext);

        $quantityColumnPath = sprintf(DatagridConfiguration::COLUMN_PATH, $this->quantityColumnName);
        $quantityColumnConfig = $configuration->offsetGetByPath($quantityColumnPath, []);

        unset($quantityColumnConfig['frontend_type']);
        $quantityColumnConfig['type'] = self::TEMPLATE_TYPE;
        $quantityColumnConfig['template'] = $this->quantityTemplate;
        $quantityColumnConfig['context'] = $templateContext;

        $configuration->offsetSetByPath($quantityColumnPath, $quantityColumnConfig);
    }
}
