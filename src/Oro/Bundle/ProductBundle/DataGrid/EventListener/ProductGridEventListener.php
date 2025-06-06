<?php

namespace Oro\Bundle\ProductBundle\DataGrid\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;

/**
 * Updates configuration of products grid and add product image on it
 */
class ProductGridEventListener
{
    public function __construct(
        private ManagerRegistry $registry,
        private SelectedFieldsProviderInterface $selectedFieldsProvider,
        private ConfigManager $configManager
    ) {
    }

    public function onResultAfter(OrmResultAfter $event): void
    {
        $datagrid = $event->getDatagrid();

        $selectedFields = $this->selectedFieldsProvider
            ->getSelectedFields($datagrid->getConfig(), $datagrid->getParameters());

        if (!in_array('productImage', $selectedFields, true)) {
            return;
        }

        /** @var ResultRecordInterface[] $records */
        $records = $event->getRecords();
        if (!$records) {
            return;
        }

        $ids = [];
        foreach ($records as $record) {
            $ids[] = $record->getValue('id');
        }

        /** @var ProductRepository $repository */
        $repository = $this->registry->getRepository(Product::class);

        $images = $repository->getListingAndMainImagesFilesByProductIds($ids);

        foreach ($records as $record) {
            $id = $record->getValue('id');

            if (isset($images[$id][ProductImageType::TYPE_LISTING])) {
                $record->setValue('productImageListing', $images[$id][ProductImageType::TYPE_LISTING]);
            }

            if (isset($images[$id][ProductImageType::TYPE_MAIN])) {
                $record->setValue('productImageMain', $images[$id][ProductImageType::TYPE_MAIN]);
            }
        }

        $event->setRecords($records);
    }

    public function onBuildAfter(BuildAfter $event): void
    {
        $datagrid = $event->getDatagrid();
        $gridConfig = $datagrid->getConfig();
        $entityClassName = $gridConfig->getExtendedEntityClassName();
        $filtersColumnConfigs = $gridConfig->offsetGetByPath('[filters][columns]');
        $filtersColumnNames = array_keys($filtersColumnConfigs);

        foreach ($filtersColumnNames as $columnName) {
            $fieldConfig = $this->configManager->getConfigFieldModel($entityClassName, $columnName);
            $gridFieldConfig = $fieldConfig?->toArray('datagrid') ?? [];
            if (!array_key_exists('show_filter', $gridFieldConfig)) {
                continue;
            }

            if ($gridFieldConfig['show_filter'] === false) {
                $gridConfig->removeFilter($columnName);
            }
        }
    }
}
