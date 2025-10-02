<?php

namespace Oro\Bundle\ShoppingListBundle\Processor;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorInterface;
use Oro\Bundle\ProductBundle\Model\Mapping\ProductMapperInterface;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Provides common functionality to handle logic related to quick order process.
 */
abstract class AbstractShoppingListQuickAddProcessor implements ComponentProcessorInterface
{
    protected ShoppingListLineItemHandler $shoppingListLineItemHandler;
    private ProductMapperInterface $productMapper;

    public function __construct(
        ShoppingListLineItemHandler $shoppingListLineItemHandler,
        ProductMapperInterface $productMapper
    ) {
        $this->shoppingListLineItemHandler = $shoppingListLineItemHandler;
        $this->productMapper = $productMapper;
    }

    protected function fillShoppingList(ShoppingList $shoppingList, array $data): int
    {
        if (empty($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY])) {
            return 0;
        }

        $items = new ArrayCollection();
        foreach ($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] as $dataItem) {
            $items->add(new \ArrayObject($dataItem));
        }
        $this->productMapper->mapProducts($items);

        $productIds = [];
        $productUnitsWithQuantities = [];
        foreach ($items as $item) {
            $productId = $item[ProductDataStorage::PRODUCT_ID_KEY] ?? null;
            if (null === $productId) {
                continue;
            }

            $productIds[] = $productId;
            if (isset($item[ProductDataStorage::PRODUCT_UNIT_KEY])) {
                $productUnit = $item[ProductDataStorage::PRODUCT_UNIT_KEY];
                $productQuantity = $item[ProductDataStorage::PRODUCT_QUANTITY_KEY];
                if (isset($productUnitsWithQuantities[$productId][$productUnit])) {
                    $productQuantity += $productUnitsWithQuantities[$productId][$productUnit];
                }
                $productUnitsWithQuantities[$productId][$productUnit] = $productQuantity;
            }
        }
        $productIds = array_values(array_unique($productIds));

        if (empty($productIds)) {
            return 0;
        }

        try {
            return $this->shoppingListLineItemHandler->createForShoppingList(
                $shoppingList,
                $productIds,
                $productUnitsWithQuantities
            );
        } catch (AccessDeniedException $e) {
            return 0;
        }
    }

    #[\Override]
    public function isAllowed(): bool
    {
        return $this->shoppingListLineItemHandler->isAllowed();
    }
}
