<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\DraftSession\Factory;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Factory\EntityDraftFactoryInterface;

/**
 * Creates an OrderLineItem draft from a shopping list LineItem, mapping all matching fields and relations.
 */
class OrderLineItemDraftFromShoppingListFactory implements EntityDraftFactoryInterface
{
    public function __construct(
        private readonly EntityDraftSyncReferenceResolver $draftSyncReferenceResolver
    ) {
    }

    #[\Override]
    public function supports(string $entityClass): bool
    {
        return is_a($entityClass, LineItem::class, true);
    }

    #[\Override]
    public function createDraft(EntityDraftAwareInterface $entity, string $draftSessionUuid): OrderLineItem
    {
        assert($entity instanceof LineItem);

        $orderLineItemDraft = new OrderLineItem();
        $orderLineItemDraft->setDraftSessionUuid($draftSessionUuid);

        $this->synchronizeFields($entity, $orderLineItemDraft);
        $this->synchronizeKitItemLineItems($entity, $orderLineItemDraft);

        return $orderLineItemDraft;
    }

    private function synchronizeFields(LineItem $lineItem, OrderLineItem $orderLineItemDraft): void
    {
        $product = $this->getReference($lineItem->getProduct());
        $orderLineItemDraft->setProduct($product);
        $orderLineItemDraft->setProductSku($lineItem->getProductSku());

        $productUnit = $this->getReference($lineItem->getProductUnit());
        if (!$productUnit) {
            $primaryUnitPrecision = $product?->getPrimaryUnitPrecision();
            if ($primaryUnitPrecision !== null) {
                $productUnit = $this->getReference($primaryUnitPrecision->getUnit());
            }
        }

        $orderLineItemDraft->setProductUnit($productUnit);
        $orderLineItemDraft->setProductUnitCode($productUnit?->getCode());
        $orderLineItemDraft->setQuantity($lineItem->getQuantity() ?: 1);

        $orderLineItemDraft->setComment($lineItem->getNotes());
    }

    private function synchronizeKitItemLineItems(
        LineItem $lineItem,
        OrderLineItem $orderLineItemDraft
    ): void {
        foreach ($lineItem->getKitItemLineItems() as $sourceKitItem) {
            $targetKitItem = new OrderProductKitItemLineItem();
            $targetKitItem->setDraftSessionUuid($orderLineItemDraft->getDraftSessionUuid());
            $this->synchronizeKitItemLineItemFields($sourceKitItem, $targetKitItem);
            $orderLineItemDraft->addKitItemLineItem($targetKitItem);
        }
    }

    private function synchronizeKitItemLineItemFields(
        ProductKitItemLineItem $sourceKitItemLineItem,
        OrderProductKitItemLineItem $targetKitItemLineItem
    ): void {
        $kitItem = $this->getReference($sourceKitItemLineItem->getKitItem());
        $targetKitItemLineItem->setKitItem($kitItem);
        $targetKitItemLineItem->setSortOrder($sourceKitItemLineItem->getSortOrder());

        $product = $sourceKitItemLineItem->getProduct();
        $targetKitItemLineItem->setProduct($this->getReference($product));
        $targetKitItemLineItem->setQuantity($sourceKitItemLineItem->getQuantity());
        $targetKitItemLineItem->setProductUnit($this->getReference($sourceKitItemLineItem->getProductUnit()));
    }

    private function getReference(?object $entity): ?object
    {
        return $this->draftSyncReferenceResolver->getReference($entity);
    }
}
