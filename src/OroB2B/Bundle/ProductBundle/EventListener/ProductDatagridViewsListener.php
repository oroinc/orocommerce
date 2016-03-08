<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

use OroB2B\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;

class ProductDatagridViewsListener
{
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
     * @param DataGridThemeHelper $themeHelper
     * @param RegistryInterface $registry
     * @param AttachmentManager $attachmentManager
     */
    public function __construct(
        DataGridThemeHelper $themeHelper,
        RegistryInterface $registry,
        AttachmentManager $attachmentManager
    ) {
        $this->themeHelper = $themeHelper;
        $this->registry = $registry;
        $this->attachmentManager = $attachmentManager;
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event)
    {
        $config = $event->getConfig();
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

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        $gridName = $event->getDatagrid()->getName();
        $supportedViews = [DataGridThemeHelper::VIEW_GRID, DataGridThemeHelper::VIEW_TILES];
        if (!in_array($this->themeHelper->getTheme($gridName), $supportedViews, true)) {
            return;
        }
        /** @var ProductRepository $repository */
        $repository = $this->registry->getEntityManagerForClass('OroB2BProductBundle:Product')
            ->getRepository('OroB2BProductBundle:Product');

        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        /** @var Product[] $products */
        $products = $repository->getProductsWithImage(array_map(function (ResultRecord $record) {
            return $record->getValue('id');
        }, $records));

        foreach ($records as $record) {
            $imageUrl = null;
            $productId = $record->getValue('id');
            foreach ($products as $product) {
                if ($product->getId() === $productId) {
                    $imageUrl = $this->attachmentManager->getAttachment(
                        'OroB2B\Bundle\ProductBundle\Entity\Product',
                        $productId,
                        'image',
                        $product->getImage()
                    );
                    break;
                }
            }
            $record->addData(['image' => $imageUrl]);
        }
    }
}
