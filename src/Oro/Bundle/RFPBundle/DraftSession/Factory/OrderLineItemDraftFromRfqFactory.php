<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\DraftSession\Factory;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\ProductBundle\Provider\DefaultProductUnitProviderInterface;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Factory\EntityDraftFactoryInterface;

/**
 * Creates an OrderLineItem draft from an RFQ RequestProduct, mapping all matching fields and relations.
 */
class OrderLineItemDraftFromRfqFactory implements EntityDraftFactoryInterface
{
    public function __construct(
        private readonly EntityDraftSyncReferenceResolver $draftSyncReferenceResolver,
        private readonly DefaultProductUnitProviderInterface $defaultProductUnitProvider,
    ) {
    }

    #[\Override]
    public function supports(string $entityClass): bool
    {
        return is_a($entityClass, RequestProduct::class, true);
    }

    #[\Override]
    public function createDraft(EntityDraftAwareInterface $entity, string $draftSessionUuid): OrderLineItem
    {
        assert($entity instanceof RequestProduct);

        $orderLineItemDraft = new OrderLineItem();
        $orderLineItemDraft->setDraftSessionUuid($draftSessionUuid);

        $this->synchronizeFields($entity, $orderLineItemDraft);
        $this->synchronizeKitItemLineItems($entity, $orderLineItemDraft);

        return $orderLineItemDraft;
    }

    private function synchronizeFields(RequestProduct $requestProduct, OrderLineItem $orderLineItemDraft): void
    {
        $product = $requestProduct->getProduct();
        $orderLineItemDraft->setProduct($this->getReference($requestProduct->getProduct()));
        $orderLineItemDraft->setProductSku($requestProduct->getProductSku());

        if ($product) {
            $primaryUnitPrecision = $product->getPrimaryUnitPrecision();
            if ($primaryUnitPrecision !== null) {
                $productUnit = $primaryUnitPrecision->getUnit();
                $orderLineItemDraft->setProductUnit($this->getReference($productUnit));
                $orderLineItemDraft->setProductUnitCode($productUnit->getCode());
            }
        } else {
            // When the referenced product has been deleted, the line item is created as a free-form item
            // with the original SKU and product unit.
            $sku = $requestProduct->getProductSku();
            if ($sku) {
                $orderLineItemDraft->setFreeFormProduct($sku);
            }

            $firstItem = $requestProduct->getRequestProductItems()->first() ?: null;
            $unit = $firstItem?->getProductUnit()
                ?? $this->defaultProductUnitProvider->getDefaultProductUnitPrecision()?->getUnit();
            if ($unit !== null) {
                $orderLineItemDraft->setProductUnit($this->getReference($unit));
                $orderLineItemDraft->setProductUnitCode($unit->getCode());
            }
        }

        $orderLineItemDraft->setQuantity(1);

        $orderLineItemDraft->setComment($requestProduct->getComment());
        $orderLineItemDraft->setRequestProduct($this->getReference($requestProduct));
    }

    private function synchronizeKitItemLineItems(
        RequestProduct $requestProduct,
        OrderLineItem $orderLineItemDraft
    ): void {
        foreach ($requestProduct->getKitItemLineItems() as $sourceKitItem) {
            $targetKitItem = new OrderProductKitItemLineItem();
            $targetKitItem->setDraftSessionUuid($orderLineItemDraft->getDraftSessionUuid());
            $this->synchronizeKitItemLineItemFields($sourceKitItem, $targetKitItem);
            $orderLineItemDraft->addKitItemLineItem($targetKitItem);
        }
    }

    private function synchronizeKitItemLineItemFields(
        RequestProductKitItemLineItem $sourceKitItemLineItem,
        OrderProductKitItemLineItem $targetKitItemLineItem
    ): void {
        $targetKitItemLineItem->setKitItem($this->getReference($sourceKitItemLineItem->getKitItem()));
        $targetKitItemLineItem->setKitItemId($sourceKitItemLineItem->getKitItemId());
        $targetKitItemLineItem->setKitItemLabel($sourceKitItemLineItem->getKitItemLabel());
        $targetKitItemLineItem->setOptional($sourceKitItemLineItem->isOptional());
        $targetKitItemLineItem->setMinimumQuantity($sourceKitItemLineItem->getMinimumQuantity());
        $targetKitItemLineItem->setMaximumQuantity($sourceKitItemLineItem->getMaximumQuantity());
        $targetKitItemLineItem->setSortOrder($sourceKitItemLineItem->getSortOrder());

        $targetKitItemLineItem->setProduct($this->getReference($sourceKitItemLineItem->getProduct()));
        $targetKitItemLineItem->setProductId($sourceKitItemLineItem->getProductId());
        $targetKitItemLineItem->setProductSku($sourceKitItemLineItem->getProductSku());
        $targetKitItemLineItem->setProductName($sourceKitItemLineItem->getProductName());
        $targetKitItemLineItem->setQuantity($sourceKitItemLineItem->getQuantity());

        $targetKitItemLineItem->setProductUnit($this->getReference($sourceKitItemLineItem->getProductUnit()));
        $targetKitItemLineItem->setProductUnitCode($sourceKitItemLineItem->getProductUnitCode());
        $targetKitItemLineItem->setProductUnitPrecision($sourceKitItemLineItem->getProductUnitPrecision());
    }

    private function getReference(?object $entity): ?object
    {
        return $this->draftSyncReferenceResolver->getReference($entity);
    }
}
