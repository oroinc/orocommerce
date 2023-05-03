<?php

namespace Oro\Bundle\ProductBundle\DataGrid\EventListener\FrontendLineItemsGrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Provider\ProductMatrixAvailabilityProvider;

/**
 * Enables update_configurable action if configurable product supports matrix form.
 */
class LineItemsMatrixFormOnResultAfterListener
{
    /** @var ProductMatrixAvailabilityProvider */
    private $productMatrixAvailabilityProvider;

    public function __construct(ProductMatrixAvailabilityProvider $productMatrixAvailabilityProvider)
    {
        $this->productMatrixAvailabilityProvider = $productMatrixAvailabilityProvider;
    }

    public function onResultAfter(OrmResultAfter $event): void
    {
        $records = $event->getRecords();
        $isMatrixFormAvailable = $this->productMatrixAvailabilityProvider
            ->isMatrixFormAvailableForProducts($this->getConfigurableProducts($records));

        foreach ($records as $record) {
            if (!$record->getValue('isConfigurable')) {
                continue;
            }

            $productId = $this->getConfigurableProduct($record)->getId();
            $record->setValue('isMatrixFormAvailable', isset($isMatrixFormAvailable[$productId]));
        }
    }

    /**
     * @param ResultRecordInterface[] $records
     * @return array
     */
    private function getConfigurableProducts(array $records): array
    {
        $products = [];
        foreach ($records as $record) {
            if (!$record->getValue('isConfigurable')) {
                continue;
            }

            $parentProduct = $this->getConfigurableProduct($record);
            $products[$parentProduct->getId()] = $parentProduct;
        }

        return $products;
    }

    private function getConfigurableProduct(ResultRecordInterface $record): Product
    {
        /** @var ProductLineItemInterface[] $firstLineItem */
        $lineItems = $record->getValue(LineItemsDataOnResultAfterListener::LINE_ITEMS) ?? [];
        $firstLineItem = reset($lineItems);
        if (!$firstLineItem instanceof ProductLineItemInterface) {
            throw new \LogicException(
                sprintf('Element lineItemsByIds was expected to contain %s objects', ProductLineItemInterface::class)
            );
        }

        return $firstLineItem->getParentProduct() ?: $firstLineItem->getProduct();
    }
}
