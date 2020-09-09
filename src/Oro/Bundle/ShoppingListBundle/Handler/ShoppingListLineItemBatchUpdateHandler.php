<?php

namespace Oro\Bundle\ShoppingListBundle\Handler;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Model\LineItemModel;

/**
 * The handler for the line item batch update.
 */
class ShoppingListLineItemBatchUpdateHandler
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ShoppingListManager */
    private $shoppingListManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ShoppingListManager $shoppingListManager
     */
    public function __construct(DoctrineHelper $doctrineHelper, ShoppingListManager $shoppingListManager)
    {
        $this->shoppingListManager = $shoppingListManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param LineItemModel[] $collection
     * @param ShoppingList $shoppingList
     */
    public function process(array $collection, ShoppingList $shoppingList): void
    {
        $lineItems = $this->getLineItems(
            array_map(
                static function (LineItemModel $model) {
                    return $model->getId();
                },
                $collection
            )
        );

        $products = [];
        $unitCodes = [];

        foreach ($collection as $lineItemModel) {
            if (isset($lineItems[$lineItemModel->getId()])) {
                $lineItem = $lineItems[$lineItemModel->getId()];

                $products[] = $lineItem->getProduct();
                $unitCodes[] = $lineItemModel->getUnitCode();
            }
        }

        /** @var ProductUnitRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(ProductUnit::class);
        $productUnits = $repository->getProductsUnitsByCodes($products, $unitCodes);

        foreach ($collection as $lineItemModel) {
            if (isset($lineItems[$lineItemModel->getId()], $productUnits[$lineItemModel->getUnitCode()])) {
                $lineItem = $lineItems[$lineItemModel->getId()];
                $lineItem->setQuantity($lineItemModel->getQuantity());
                $lineItem->setUnit($productUnits[$lineItemModel->getUnitCode()]);

                $this->shoppingListManager->updateLineItem($lineItem, $shoppingList);
            }
        }
    }

    /**
     * @param array $ids
     * @return LineItem[]
     */
    private function getLineItems(array $ids): array
    {
        $repository = $this->doctrineHelper->getEntityRepository(LineItem::class);

        $data = [];
        foreach ($repository->findBy(['id' => $ids]) as $item) {
            $data[$item->getId()] = $item;
        }

        return $data;
    }
}
