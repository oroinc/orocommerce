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
    private const COLUMN_SHORT_DESCRIPTION = 'shortDescription';
    private const COLUMN_PRODUCT_UNITS = 'product_units';
    private const COLUMN_HAS_IMAGE = 'hasImage';
    private const COLUMN_IMAGE = 'image';

    private DataGridThemeHelper $themeHelper;
    private ImagePlaceholderProviderInterface $imagePlaceholderProvider;

    public function __construct(
        DataGridThemeHelper $themeHelper,
        ImagePlaceholderProviderInterface $imagePlaceholderProvider
    ) {
        $this->themeHelper = $themeHelper;
        $this->imagePlaceholderProvider = $imagePlaceholderProvider;
    }

    public function onPreBuild(PreBuild $event): void
    {
        $config = $event->getConfig();

        $config->offsetAddToArrayByPath(
            '[properties]',
            [
                self::COLUMN_PRODUCT_UNITS => [
                    'type' => 'field',
                    'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY
                ],
                self::COLUMN_HAS_IMAGE => [
                    'type' => 'field',
                    'frontend_type' => PropertyInterface::TYPE_BOOLEAN
                ]
            ]
        );

        // add theme processing
        $this->updateConfigByView($config, $this->themeHelper->getTheme($config->getName()));
    }

    public function onResultAfter(SearchResultAfter $event): void
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $this->addProductUnits($records);
        $this->addProductImages($event, $records);
    }

    private function updateConfigByView(DatagridConfiguration $config, string $viewName): void
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

    private function addImageToConfig(DatagridConfiguration $config): void
    {
        $config->offsetAddToArrayByPath(
            '[columns]',
            [
                self::COLUMN_IMAGE => [
                    'label' => 'oro.product.image.label'
                ]
            ]
        );
    }

    private function addShortDescriptionToConfig(DatagridConfiguration $config): void
    {
        $config->offsetAddToArrayByPath(
            '[columns]',
            [
                self::COLUMN_SHORT_DESCRIPTION => [
                    'label' => 'oro.product.short_descriptions.label'
                ]
            ]
        );
        $config->offsetAddToArrayByPath(
            '[properties]',
            [
                self::COLUMN_SHORT_DESCRIPTION => [
                    'type' => LocalizedValueProperty::NAME,
                    'data_name' => 'shortDescriptions'
                ]
            ]
        );
    }

    /**
     * @param SearchResultAfter $event
     * @param ResultRecord[] $records
     */
    private function addProductImages(SearchResultAfter $event, array $records): void
    {
        $gridName = $event->getDatagrid()->getName();
        $theme = $this->themeHelper->getTheme($gridName);
        switch ($theme) {
            case DataGridThemeHelper::VIEW_GRID:
                $imageFilter = 'product_large';
                break;
            case DataGridThemeHelper::VIEW_LIST:
            case DataGridThemeHelper::VIEW_TILES:
                $imageFilter = 'product_medium';
                break;
            default:
                return;
        }

        $noImagePath = false;
        foreach ($records as $record) {
            $hasProductImage = true;
            $productImageUrl = $record->getValue('image_' . $imageFilter);
            if (!$productImageUrl) {
                if (false === $noImagePath) {
                    $noImagePath = $this->imagePlaceholderProvider->getPath($imageFilter);
                }
                $hasProductImage = false;
                $productImageUrl = $noImagePath;
            }
            $record->addData([
                self::COLUMN_HAS_IMAGE => $hasProductImage,
                self::COLUMN_IMAGE => $productImageUrl
            ]);
        }
    }

    /**
     * @param ResultRecord[] $records
     */
    private function addProductUnits(array $records): void
    {
        foreach ($records as $record) {
            $productUnits = $record->getValue('product_units');
            $record->addData([
                self::COLUMN_PRODUCT_UNITS => $productUnits
                    ? unserialize($productUnits, ['allowed_classes' => false])
                    : []
            ]);
        }
    }
}
