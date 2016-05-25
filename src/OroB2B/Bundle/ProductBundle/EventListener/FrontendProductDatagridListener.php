<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\ORM\Query\Expr;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

use OroB2B\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class FrontendProductDatagridListener
{
    const COLUMN_PRODUCT_UNITS = 'product_units';

    const DATA_SEPARATOR = '{sep}';
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
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param DataGridThemeHelper $themeHelper
     * @param RegistryInterface $registry
     * @param AttachmentManager $attachmentManager
     * @param ProductUnitLabelFormatter $unitFormatter
     * @param TranslatorInterface $translator
     */
    public function __construct(
        DataGridThemeHelper $themeHelper,
        RegistryInterface $registry,
        AttachmentManager $attachmentManager,
        ProductUnitLabelFormatter $unitFormatter,
        TranslatorInterface $translator
    ) {
        $this->themeHelper = $themeHelper;
        $this->registry = $registry;
        $this->attachmentManager = $attachmentManager;
        $this->unitFormatter = $unitFormatter;
        $this->translator = $translator;
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event)
    {
        $config = $event->getConfig();

        // add all product units
        $select = sprintf(
            'GROUP_CONCAT(IDENTITY(unit_precisions.unit) SEPARATOR %s) as %s',
            (new Expr())->literal(self::DATA_SEPARATOR),
            self::COLUMN_PRODUCT_UNITS
        );
        $config->offsetAddToArrayByPath('[source][query][select]', [$select]);
        $config->offsetAddToArrayByPath(
            '[source][query][join][left]',
            [['join' => 'product.unitPrecisions', 'alias' => 'unit_precisions', "conditionType" => 'WITH', 'condition' =>'unit_precisions.sell=true']]
        );
        $config->offsetAddToArrayByPath(
            '[properties]',
            [self::COLUMN_PRODUCT_UNITS => ['type' => 'field', 'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY]]
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
            '[source][query][select]' => [
                'productImage.filename as image',
            ],
            '[source][query][join][left]' => [
                [
                    'join' => 'product.image',
                    'alias' => 'productImage',
                ]
            ],
            '[columns]' => [
                'image' => [
                    'label' => $this->translator->trans('orob2b.product.image.label'),
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
                    'condition' => 'productShortDescriptions.locale IS NULL'
                ]
            ],
            '[columns]' => [
                'shortDescription' => [
                    'label' => $this->translator->trans('orob2b.product.short_descriptions.label'),
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

        // handle product units
        foreach ($records as $record) {
            $units = [];
            $concatenatedUnits = $record->getValue(self::COLUMN_PRODUCT_UNITS);
            if ($concatenatedUnits) {
                foreach (explode(self::DATA_SEPARATOR, $concatenatedUnits) as $unitCode) {
                    $units[$unitCode] = $this->unitFormatter->format($unitCode);
                }
            }
            $record->addData([self::COLUMN_PRODUCT_UNITS => $units]);
        }

        // handle views
        $gridName = $event->getDatagrid()->getName();
        $supportedViews = [DataGridThemeHelper::VIEW_GRID, DataGridThemeHelper::VIEW_TILES];
        if (!in_array($this->themeHelper->getTheme($gridName), $supportedViews, true)) {
            return;
        }

        /** @var ProductRepository $repository */
        $repository = $this->registry->getManagerForClass('OroB2BProductBundle:Product')
            ->getRepository('OroB2BProductBundle:Product');

        /** @var Product[] $products */
        $products = $repository->getProductsWithImage(array_map(function (ResultRecord $record) {
            return $record->getValue('id');
        }, $records));

        foreach ($records as $record) {
            $imageUrl = null;
            $productId = $record->getValue('id');
            foreach ($products as $product) {
                if ($product->getId() === $productId) {
                    $imageUrl = $this->attachmentManager->getFilteredImageUrl(
                        $product->getImage(),
                        self::PRODUCT_IMAGE_FILTER
                    );
                    break;
                }
            }
            $record->addData(['image' => $imageUrl]);
        }
    }
}
