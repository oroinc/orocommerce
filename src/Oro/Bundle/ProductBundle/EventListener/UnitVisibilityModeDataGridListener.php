<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeServiceInterface;

class UnitVisibilityModeDataGridListener
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
     * @param SingleUnitModeServiceInterface $singleUnitModeService
     */
    public function __construct(
        $unitColumnName,
        $quantityColumnName,
        $quantityTemplate,
        array $quantityTemplateContext,
        SingleUnitModeServiceInterface $singleUnitModeService
    ) {
        $this->unitColumnName = $unitColumnName;
        $this->quantityColumnName = $quantityColumnName;
        $this->quantityTemplate = $quantityTemplate;
        $this->quantityTemplateContext = $quantityTemplateContext;
        $this->singleUnitModeService = $singleUnitModeService;
    }

    public function onBuildBefore(BuildBefore $event)
    {
        if (!$this->singleUnitModeService->isSingleUnitMode()) {
            return;
        }

        $config = $event->getDatagrid()->getConfig();

        $config->removeColumn($this->unitColumnName);
        $this->addUnitLabelToQuantityColumn($config);
    }

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
