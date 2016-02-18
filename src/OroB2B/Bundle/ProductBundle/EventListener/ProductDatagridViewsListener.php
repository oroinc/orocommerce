<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

use OroB2B\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;

class ProductDatagridViewsListener
{
    /**
     * @var DataGridThemeHelper
     */
    protected $themeHelper;

    /**
     * @param DataGridThemeHelper $themeHelper
     */
    public function __construct(DataGridThemeHelper $themeHelper)
    {
        $this->themeHelper = $themeHelper;
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event)
    {
        $config = $event->getConfig();
        $gridName = $config->getName();
        $viewName = $this->themeHelper->getTheme($gridName);
        if (!$viewName) {
            return;
        }
        $this->updateConfigByView($config, $viewName);
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $viewName
     */
    protected function updateConfigByView(DatagridConfiguration $config, $viewName)
    {
        switch ($viewName) {
            case DataGridThemeHelper::VIEW_GRID:
                // grid view same as default
                break;
            case DataGridThemeHelper::VIEW_LIST:
                $updates = [
                    '[source][query][select]' => [
                        'productImage.filename as image',
                        'productDescriptions.string as description'
                    ],
                    '[source][query][join][left]' => [
                        [
                            'join' => 'product.image',
                            'alias' => 'productImage',
                        ]
                    ],
                    '[source][query][join][inner]' => [
                        [
                            'join' => 'product.descriptions',
                            'alias' => 'productDescriptions',
                            'conditionType' => 'WITH',
                            'condition' => 'productDescriptions.locale IS NULL'
                        ]
                    ],
                ];
                break;
            case DataGridThemeHelper::VIEW_TILES:
                $updates = [
                    '[source][query][select]' => [
                        'productImage.filename as image',
                    ],
                    '[source][query][join][left]' => [
                        [
                            'join' => 'product.image',
                            'alias' => 'productImage',
                        ]
                    ],
                ];
                break;
        }
        if (isset($updates)) {
            foreach ($updates as $path => $update) {
                $config->offsetAddToArrayByPath($path, $update);
            }
        }
    }
}
