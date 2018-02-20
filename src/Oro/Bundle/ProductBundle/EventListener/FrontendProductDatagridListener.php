<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\LocaleBundle\Datagrid\Formatter\Property\LocalizedValueProperty;
use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Symfony\Bridge\Doctrine\RegistryInterface;

class FrontendProductDatagridListener
{
    const COLUMN_PRODUCT_UNITS = 'product_units';

    const PRODUCT_IMAGE_FILTER_LARGE = 'product_large';
    const PRODUCT_IMAGE_FILTER_MEDIUM = 'product_medium';

    const DEFAULT_IMAGE = '/bundles/oroproduct/default/images/no_image.png';

    /**
     * @var DataGridThemeHelper
     */
    protected $themeHelper;

    /**
     * @var RegistryInterface
     *
     * @deprecated Will be removed in 1.4
     */
    protected $registry;

    /**
     * @var AttachmentManager
     */
    protected $attachmentManager;

    /**
     * @var CacheManager
     */
    protected $imagineCacheManager;

    /**
     * @param DataGridThemeHelper $themeHelper
     * @param RegistryInterface $registry
     * @param AttachmentManager $attachmentManager
     * @param CacheManager $imagineCacheManager
     */
    public function __construct(
        DataGridThemeHelper $themeHelper,
        RegistryInterface $registry,
        AttachmentManager $attachmentManager,
        CacheManager $imagineCacheManager
    ) {
        $this->themeHelper = $themeHelper;
        $this->registry = $registry;
        $this->attachmentManager = $attachmentManager;
        $this->imagineCacheManager = $imagineCacheManager;
    }

    /**
     * @param PreBuild $event
     */
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

    /**
     * @param DatagridConfiguration $config
     */
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

    /**
     * @param DatagridConfiguration $config
     */
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

    /**
     * @param DatagridConfiguration $config
     * @param array $updates
     */
    protected function applyUpdatesToConfig(DatagridConfiguration $config, array $updates)
    {
        foreach ($updates as $path => $update) {
            $config->offsetAddToArrayByPath($path, $update);
        }
    }

    /**
     * @param SearchResultAfter $event
     */
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

        $defaultImageUrl = $this->imagineCacheManager->getBrowserPath(self::DEFAULT_IMAGE, $imageFilter);

        foreach ($records as $record) {
            $productImageUrl = $record->getValue('image_' . $imageFilter);

            if (!$productImageUrl) {
                $productImageUrl = $defaultImageUrl;
            }
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

    /**
     * @return ProductRepository
     *
     * @deprecated Will be removed in 1.4
     */
    protected function getProductRepository()
    {
        return $this->registry
            ->getManagerForClass('OroProductBundle:Product')
            ->getRepository('OroProductBundle:Product');
    }

    /**
     * @return ProductUnitRepository
     *
     * @deprecated Will be removed in 1.4
     */
    protected function getProductUnitRepository()
    {
        return $this->registry
            ->getManagerForClass('OroProductBundle:ProductUnit')
            ->getRepository('OroProductBundle:ProductUnit');
    }
}
