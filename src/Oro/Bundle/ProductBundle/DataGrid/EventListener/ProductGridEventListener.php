<?php

namespace Oro\Bundle\ProductBundle\DataGrid\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;

/**
 * Updates configuration of products grid and add product image on it
 */
class ProductGridEventListener
{
    /** @var ManagerRegistry */
    private $registry;

    /** @var SelectedFieldsProviderInterface */
    private $selectedFieldsProvider;

    public function __construct(ManagerRegistry $registry, SelectedFieldsProviderInterface $selectedFieldsProvider)
    {
        $this->registry = $registry;
        $this->selectedFieldsProvider = $selectedFieldsProvider;
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
}
