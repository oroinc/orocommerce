<?php

namespace Oro\Bundle\ShoppingListBundle\Storage;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage as Storage;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Service for storing product data in session
 */
class ProductDataStorage
{
    private Storage $storage;

    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    public function saveToStorage(ShoppingList $shoppingList): void
    {
        $data = [
            Storage::ENTITY_DATA_KEY => [
                'customerUser' => $shoppingList->getCustomerUser()?->getId(),
                'customer' => $shoppingList->getCustomer()?->getId(),
                'sourceEntityId' => $shoppingList->getId(),
                'sourceEntityClass' => ClassUtils::getClass($shoppingList),
                'sourceEntityIdentifier' => $shoppingList->getIdentifier(),
                'note' => $shoppingList->getNotes(),
            ],
        ];

        foreach ($shoppingList->getLineItems() as $lineItem) {
            $data[Storage::ENTITY_ITEMS_DATA_KEY][] = [
                Storage::PRODUCT_SKU_KEY => $lineItem->getProduct()->getSku(),
                Storage::PRODUCT_ID_KEY => $lineItem->getProduct()->getId(),
                Storage::PRODUCT_QUANTITY_KEY => $lineItem->getQuantity(),
                'comment' => $lineItem->getNotes(),
                'productUnit' => $lineItem->getUnit()->getCode(),
                'productUnitCode' => $lineItem->getUnit()->getCode(),
                Storage::PRODUCT_KIT_ITEM_LINE_ITEMS_DATA_KEY => $this->getKitItemLineItemsData($lineItem),
            ];
        }

        $this->storage->set($data);
    }

    protected function getKitItemLineItemsData(LineItem $lineItem): array
    {
        $kitItemLineItemsData = [];
        foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
            $kitItemLineItemsData[] = [
                Storage::PRODUCT_KIT_ITEM_LINE_ITEM_KIT_ITEM_KEY => $kitItemLineItem->getKitItem()?->getId(),
                Storage::PRODUCT_KIT_ITEM_LINE_ITEM_PRODUCT_KEY => $kitItemLineItem->getProduct()?->getId(),
                Storage::PRODUCT_KIT_ITEM_LINE_ITEM_PRODUCT_UNIT_KEY =>
                    $kitItemLineItem->getProductUnit()?->getCode(),
                Storage::PRODUCT_KIT_ITEM_LINE_ITEM_QUANTITY_KEY => $kitItemLineItem->getQuantity(),
            ];
        }

        return $kitItemLineItemsData;
    }
}
