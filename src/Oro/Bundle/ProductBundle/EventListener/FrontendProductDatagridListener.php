<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Bundle\LocaleBundle\Datagrid\Formatter\Property\LocalizedValueProperty;
use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;

/**
 * Prepare product items based on product grid view option
 */
class FrontendProductDatagridListener
{
    const COLUMN_PRODUCT_UNITS = 'product_units';

    const PRODUCT_IMAGE_FILTER_LARGE = 'product_large';
    const PRODUCT_IMAGE_FILTER_MEDIUM = 'product_medium';
    const PRODUCT_IMAGE_FILTER_SMALL = 'product_small';

    /**
     * @var DataGridThemeHelper
     */
    protected $themeHelper;

    /**
     * @var ImagePlaceholderProviderInterface
     */
    protected $imagePlaceholderProvider;

    public function __construct(
        DataGridThemeHelper $themeHelper,
        ImagePlaceholderProviderInterface $imagePlaceholderProvider
    ) {
        $this->themeHelper = $themeHelper;
        $this->imagePlaceholderProvider = $imagePlaceholderProvider;
    }

    public function onPreBuild(PreBuild $event)
    {
        $config = $event->getConfig();

        $config->offsetAddToArrayByPath(
            '[properties]',
            [self::COLUMN_PRODUCT_UNITS => [
                'type' => 'field',
                'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY]
            ]
        );

        // add theme processing
        $gridName = $config->getName();
        $this->updateConfigByView($config, $this->themeHelper->getTheme($gridName));
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $viewName
     */
    protected function updateConfigByView(DatagridConfiguration $config, $viewName)
    {
        switch ($viewName) {
            case DataGridThemeHelper::VIEW_LIST:
                // grid view same as default
                $this->addImageToConfig($config);
                break;
            case DataGridThemeHelper::VIEW_GRID:
                $this->addImageToConfig($config);
                $this->addShortDescriptionToConfig($config);
                break;
            case DataGridThemeHelper::VIEW_TILES:
                $this->addImageToConfig($config);
                break;
        }
    }

    protected function addImageToConfig(DatagridConfiguration $config)
    {
        $updates = [
            '[columns]' => [
                'image' => [
                    'label' => 'oro.product.image.label',
                ]
            ],
        ];
        $this->applyUpdatesToConfig($config, $updates);
    }

    protected function addShortDescriptionToConfig(DatagridConfiguration $config)
    {
        $updates = [
            '[columns]' => [
                'shortDescription' => [
                    'label' => 'oro.product.short_descriptions.label',
                ]
            ],
            '[properties]' => [
                'shortDescription' => [
                    'type' => LocalizedValueProperty::NAME,
                    'data_name' => 'shortDescriptions',
                ]
            ],
        ];
        $this->applyUpdatesToConfig($config, $updates);
    }

    protected function applyUpdatesToConfig(DatagridConfiguration $config, array $updates)
    {
        foreach ($updates as $path => $update) {
            $config->offsetAddToArrayByPath($path, $update);
        }
    }

    public function onResultAfter(SearchResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $this->addProductUnits($records);
        $this->addProductImages($event, $records);
    }

    /**
     * @param SearchResultAfter $event
     * @param ResultRecord[] $records
     */
    protected function addProductImages(SearchResultAfter $event, array $records)
    {
        $gridName = $event->getDatagrid()->getName();
        $theme = $this->themeHelper->getTheme($gridName);
        switch ($theme) {
            case DataGridThemeHelper::VIEW_LIST:
                $imageFilter = self::PRODUCT_IMAGE_FILTER_MEDIUM;
                break;
            case DataGridThemeHelper::VIEW_GRID:
                $imageFilter = self::PRODUCT_IMAGE_FILTER_LARGE;
                break;
            case DataGridThemeHelper::VIEW_TILES:
                $imageFilter = self::PRODUCT_IMAGE_FILTER_MEDIUM;
                break;
            default:
                return;
        }

        $noImagePath = $this->imagePlaceholderProvider->getPath($imageFilter);
        foreach ($records as $record) {
            $productImageUrl = $record->getValue('image_' . $imageFilter) ?: $noImagePath;
            $record->addData(['image' => $productImageUrl]);
        }
    }

    /**
     * @param ResultRecord[] $records
     */
    protected function addProductUnits($records)
    {
        foreach ($records as $record) {
            $productUnits = $record->getValue('product_units');
            $units = [];
            if ($productUnits) {
                $units = unserialize($productUnits);
            }
            $record->addData([self::COLUMN_PRODUCT_UNITS => $units]);
        }
    }
}
