<?php

namespace Oro\Bundle\RFPBundle\Storage;

use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;

/**
 * Extracts data from RFPRequest and puts it into storage. Saved data is used later during Order creation.
 */
class RequestToOrderDataStorage
{
    public function __construct(
        private ProductDataStorage $productDataStorage,
        private OffersDataStorage $offersDataStorage
    ) {
    }

    public function saveToStorage(RFPRequest $request): void
    {
        $data = [ProductDataStorage::ENTITY_DATA_KEY => $this->getEntityData($request)];

        $offers = [];
        foreach ($request->getRequestProducts() as $lineItem) {
            $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][] = [
                ProductDataStorage::PRODUCT_SKU_KEY => $lineItem->getProductSku(),
                ProductDataStorage::PRODUCT_ID_KEY => $lineItem->getProduct()->getId(),
                'comment' => $lineItem->getComment(),
                ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEMS_DATA_KEY => $this->getKitItemLineItemsData($lineItem),
            ];

            $itemOffers = [];
            foreach ($lineItem->getRequestProductItems() as $productItem) {
                $itemOffers[] = $this->getOfferData($productItem);
            }
            $offers[] = $itemOffers;
        }

        $this->productDataStorage->set($data);
        $this->offersDataStorage->set($offers);
    }

    private function getEntityData(Request $request): array
    {
        $data = [];

        if ($request->getCustomerUser()) {
            $data['customerUser'] = $request->getCustomerUser()->getId();
        }

        if ($request->getCustomer()) {
            $data['customer'] = $request->getCustomer()->getId();
        }

        $data['projectName'] = $request->getProjectName();
        $data['shipUntil'] = $request->getShipUntil();
        $data['poNumber'] = $request->getPoNumber();
        $data['customerNotes'] = $request->getNote();
        $data['sourceEntityId'] = $request->getId();
        $data['sourceEntityClass'] = get_class($request);
        $data['sourceEntityIdentifier'] = $request->getIdentifier();

        return $data;
    }

    private function getOfferData(RequestProductItem $productItem): array
    {
        $data = [
            'quantity' => $productItem->getQuantity(),
            'unit' => $productItem->getProductUnitCode(),
        ];

        $price = $productItem->getPrice();
        if ($price) {
            $data['currency'] = $price->getCurrency();
            $data['price'] = $price->getValue();
        }

        return $data;
    }

    private function getKitItemLineItemsData(RequestProduct $requestProduct): array
    {
        $kitItemLineItemsData = [];
        foreach ($requestProduct->getKitItemLineItems() as $kitItemLineItem) {
            $kitItemLineItemsData[] = [
                ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_KIT_ITEM_KEY => $kitItemLineItem->getKitItem()?->getId(),
                ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_PRODUCT_KEY => $kitItemLineItem->getProduct()?->getId(),
                ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_PRODUCT_UNIT_KEY =>
                    $kitItemLineItem->getProductUnit()?->getCode(),
                ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_QUANTITY_KEY => $kitItemLineItem->getQuantity(),
            ];
        }

        return $kitItemLineItemsData;
    }
}
