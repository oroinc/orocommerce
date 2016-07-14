<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\Query\Expr;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

use OroB2B\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class FrontendProductDatagridListener
{
    const COLUMN_PRODUCT_UNITS = 'product_units';
    const PRODUCT_IMAGE_FILTER = 'product_large';

    /**
     * @var DataGridThemeHelper
     */
    protected $themeHelper;

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @var AttachmentManager
     */
    protected $attachmentManager;

    /**
     * @var ProductUnitLabelFormatter
     */
    protected $unitFormatter;

    /**
     * @param DataGridThemeHelper $themeHelper
     * @param RegistryInterface $registry
     * @param AttachmentManager $attachmentManager
     * @param ProductUnitLabelFormatter $unitFormatter
     */
    public function __construct(
        DataGridThemeHelper $themeHelper,
        RegistryInterface $registry,
        AttachmentManager $attachmentManager,
        ProductUnitLabelFormatter $unitFormatter
    ) {
        $this->themeHelper = $themeHelper;
        $this->registry = $registry;
        $this->attachmentManager = $attachmentManager;
        $this->unitFormatter = $unitFormatter;
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
     * @return array
     */
    protected function addImageToConfig(DatagridConfiguration $config)
    {
        $updates = [
            '[columns]' => [
                'image' => [
                    'label' => 'orob2b.product.image.label',
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
            '[source][query][select]' => [
                'productShortDescriptions.text as shortDescription'
            ],
            '[source][query][join][inner]' => [
                [
                    'join' => 'product.shortDescriptions',
                    'alias' => 'productShortDescriptions',
                    'conditionType' => 'WITH',
                    'condition' => 'productShortDescriptions.localization IS NULL'
                ]
            ],
            '[columns]' => [
                'shortDescription' => [
                    'label' => 'orob2b.product.short_descriptions.label',
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
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $productIds = array_map(
            function (ResultRecord $record) {
                return $record->getValue('id');
            },
            $records
        );

        $this->addProductUnits($productIds, $records);
        $this->addProductImages($event, $productIds, $records);
    }

    /**
     * @param OrmResultAfter $event
     * @param array $productIds
     * @param ResultRecord[] $records
     */
    protected function addProductImages(OrmResultAfter $event, array $productIds, array $records)
    {
        $gridName = $event->getDatagrid()->getName();
        $supportedViews = [DataGridThemeHelper::VIEW_GRID, DataGridThemeHelper::VIEW_TILES];
        if (!in_array($this->themeHelper->getTheme($gridName), $supportedViews, true)) {
            return;
        }

        $productImages = $this->getProductRepository()->getListingImagesFilesByProductIds($productIds);

        foreach ($records as $record) {
            $imageUrl = null;
            $productId = $record->getValue('id');

            if (isset($productImages[$productId])) {
                $imageUrl = $this->attachmentManager->getFilteredImageUrl(
                    $productImages[$productId],
                    self::PRODUCT_IMAGE_FILTER
                );
                $record->addData(['image' => $imageUrl]);
            }
        }
    }

    /**
     * @param array $productIds
     * @param ResultRecord[] $records
     */
    protected function addProductUnits($productIds, $records)
    {
        $productUnits = $this->getProductUnitRepository()->getProductsUnits($productIds);

        foreach ($records as $record) {
            $units = [];
            $productId = $record->getValue('id');
            if (array_key_exists($productId, $productUnits)) {
                foreach ($productUnits[$productId] as $unitCode) {
                    $units[$unitCode] = $this->unitFormatter->format($unitCode);
                }
            }
            $record->addData([self::COLUMN_PRODUCT_UNITS => $units]);
        }
    }

    /**
     * @return ProductRepository
     */
    protected function getProductRepository()
    {
        return $this->registry
            ->getManagerForClass('OroB2BProductBundle:Product')
            ->getRepository('OroB2BProductBundle:Product');
    }

    /**
     * @return ProductUnitRepository
     */
    protected function getProductUnitRepository()
    {
        return $this->registry
            ->getManagerForClass('OroB2BProductBundle:ProductUnit')
            ->getRepository('OroB2BProductBundle:ProductUnit');
    }
}
