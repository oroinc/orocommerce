<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Datagrid\DraftSession;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsProviderInterface;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;

/**
 * Adds product images to order line items datagrid records.
 */
class OrderLineItemDraftImagesDatagridListener
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly SelectedFieldsProviderInterface $selectedFieldsProvider
    ) {
    }

    public function onResultAfter(OrmResultAfter $event): void
    {
        $datagrid = $event->getDatagrid();

        $selectedFields = $this->selectedFieldsProvider
            ->getSelectedFields($datagrid->getConfig(), $datagrid->getParameters());

        if (!in_array('product', $selectedFields, true)) {
            return;
        }

        /** @var ResultRecordInterface[] $records */
        $records = $event->getRecords();
        if (!$records) {
            return;
        }

        $productIds = [];
        foreach ($records as $record) {
            /** @var OrderLineItem $orderLineItem */
            $orderLineItem = $record->getRootEntity();
            if ($orderLineItem->getProduct()) {
                $productIds[] = $orderLineItem->getProduct()->getId();
            }
        }

        /** @var ProductRepository $repository */
        $repository = $this->doctrine->getRepository(Product::class);

        $images = $repository->getListingAndMainImagesFilesByProductIds($productIds);

        foreach ($records as $record) {
            /** @var OrderLineItem $orderLineItem */
            $orderLineItem = $record->getRootEntity();
            if (!$orderLineItem->getProduct()) {
                continue;
            }

            $productId = $orderLineItem->getProduct()->getId();

            if (isset($images[$productId][ProductImageType::TYPE_LISTING])) {
                $record->setValue('productImageListing', $images[$productId][ProductImageType::TYPE_LISTING]);
            }

            if (isset($images[$productId][ProductImageType::TYPE_MAIN])) {
                $record->setValue('productImageMain', $images[$productId][ProductImageType::TYPE_MAIN]);
            }
        }

        $event->setRecords($records);
    }
}
