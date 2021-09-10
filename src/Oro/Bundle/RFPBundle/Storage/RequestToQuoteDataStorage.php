<?php

namespace Oro\Bundle\RFPBundle\Storage;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;

/**
 * Extracts data from RFPRequest and puts it into storage. Saved data is used later during Quote creation.
 */
class RequestToQuoteDataStorage
{
    /** @var ProductDataStorage */
    protected $storage;

    public function __construct(ProductDataStorage $storage)
    {
        $this->storage = $storage;
    }

    public function saveToStorage(RFPRequest $rfpRequest)
    {
        $data = [
            ProductDataStorage::ENTITY_DATA_KEY => [
                'customerUser' => $rfpRequest->getCustomerUser() ? $rfpRequest->getCustomerUser()->getId() : null,
                'customer' => $rfpRequest->getCustomer() ? $rfpRequest->getCustomer()->getId() : null,
                'request' => $rfpRequest->getId(),
                'poNumber' => $rfpRequest->getPoNumber(),
                'shipUntil' => $rfpRequest->getShipUntil(),
                'assignedUsers' => $this->getEntitiesIds($rfpRequest->getAssignedUsers()),
                'assignedCustomerUsers' => $this->getEntitiesIds($rfpRequest->getAssignedCustomerUsers()),
                'website' => $rfpRequest->getWebsite() ? $rfpRequest->getWebsite()->getId() : null,
            ],
        ];

        foreach ($rfpRequest->getRequestProducts() as $requestProduct) {
            $items = $this->getRequestProductItems($requestProduct);

            $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][] = [
                ProductDataStorage::PRODUCT_SKU_KEY =>  $requestProduct->getProductSku(),
                ProductDataStorage::PRODUCT_QUANTITY_KEY => null,
                'commentCustomer' => $requestProduct->getComment(),
                'requestProductItems' => $items,
            ];
        }

        $this->storage->set($data);
    }

    /**
     * @param RequestProduct $requestProduct
     * @return array
     */
    private function getRequestProductItems(RequestProduct $requestProduct)
    {
        $items = [];
        foreach ($requestProduct->getRequestProductItems() as $requestProductItem) {
            $productUnitCode = $requestProductItem->getProductUnit()
                ? $requestProductItem->getProductUnit()->getCode()
                : null;

            $items[] = [
                'price' => $requestProductItem->getPrice(),
                'quantity' => $requestProductItem->getQuantity(),
                'productUnit' => $productUnitCode,
                'productUnitCode' => $productUnitCode,
                'requestProductItem' => $requestProductItem->getId(),
            ];
        }

        return $items;
    }

    /**
     * @param Collection $collection
     * @return array
     */
    protected function getEntitiesIds(Collection $collection)
    {
        $ids = [];

        foreach ($collection as $item) {
            if (method_exists($item, 'getId') && $item->getId()) {
                $ids[] = $item->getId();
            }
        }

        return $ids;
    }
}
