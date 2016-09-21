<?php

namespace Oro\Bundle\ShoppingListBundle\Storage;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\ProductBundle\Storage\ProductDataStorage as Storage;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ProductDataStorage
{
    /** @var Storage */
    protected $storage;

    /**
     * @param Storage $storage
     */
    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param ShoppingList $shoppingList
     */
    public function saveToStorage(ShoppingList $shoppingList)
    {
        $data = [
            Storage::ENTITY_DATA_KEY => [
                'accountUser' => $shoppingList->getAccountUser()->getId(),
                'account' => $shoppingList->getAccount()->getId(),
                'sourceEntityId' => $shoppingList->getId(),
                'sourceEntityClass' => ClassUtils::getClass($shoppingList),
                'sourceEntityIdentifier' => $shoppingList->getIdentifier(),
            ],
        ];

        foreach ($shoppingList->getLineItems() as $lineItem) {
            $data[Storage::ENTITY_ITEMS_DATA_KEY][] = [
                Storage::PRODUCT_SKU_KEY => $lineItem->getProduct()->getSku(),
                Storage::PRODUCT_QUANTITY_KEY => $lineItem->getQuantity(),
                'comment' => $lineItem->getNotes(),
                'productUnit' => $lineItem->getUnit()->getCode(),
                'productUnitCode' => $lineItem->getUnit()->getCode(),
            ];
        }

        $this->storage->set($data);
    }
}
