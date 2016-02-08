<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

use OroB2B\Bundle\ProductBundle\DataGrid\Extension\RowTemplate\Configuration as RowTemplateConfiguration;

class ProductDatagridViewsListener
{
    const GRID_TEMPLATE_PATH = 'template';
    const ROW_TEMPLATE_NAME = '%s-%s-row-template'; // [1] grid name, [2] view name
    const VIEW_GRID = 'grid';
    const VIEW_LIST = 'list';
    const VIEW_TILES = 'tiles';

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event)
    {
        $config = $event->getConfig();
        $gridName = $config->getName();
        $viewName = $this->getViewName($gridName);
        if (!$viewName) {
            return;
        }
        $this->addRowTemplate($config, $gridName, $viewName);
        $this->updateConfigByView($config, $viewName);
    }

    /**
     * @param DatagridConfiguration $config
     * @param $gridName
     * @param $viewName
     */
    protected function addRowTemplate(DatagridConfiguration $config, $gridName, $viewName)
    {
        $rowTemplateName = sprintf(self::ROW_TEMPLATE_NAME, $gridName, $viewName);
        $config->offsetSetByPath(RowTemplateConfiguration::ROW_TEMPLATE_PATH, $rowTemplateName);
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $viewName
     */
    protected function updateConfigByView(DatagridConfiguration $config, $viewName)
    {
        switch ($viewName) {
            case self::VIEW_GRID:
                // grid view same as default
                break;
            case self::VIEW_LIST:
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
            case self::VIEW_TILES:
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

    /**
     *
     * @param string $gridName
     * @return null|string
     */
    protected function getViewName($gridName)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }
        $gridParams = $request->query->get($gridName);

        if (is_array($gridParams) && array_key_exists(self::GRID_TEMPLATE_PATH, $gridParams)) {
            return $gridParams[self::GRID_TEMPLATE_PATH];
        } else {
            return null;
        }
    }
}
