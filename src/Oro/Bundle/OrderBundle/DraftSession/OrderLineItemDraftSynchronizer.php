<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\DraftSession;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\ExtendedFields\EntityDraftExtendedFieldsProvider;
use Oro\Component\DraftSession\ExtendedFields\EntityDraftExtendedFieldSynchronizer;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;

/**
 * Synchronizes OrderLineItem fields between source and target order line items during draft sync.
 */
class OrderLineItemDraftSynchronizer implements EntityDraftSynchronizerInterface
{
    public function __construct(
        private readonly EntityDraftSyncReferenceResolver $draftSyncReferenceResolver,
        private readonly EntityDraftExtendedFieldsProvider $extendedFieldsProvider,
        private readonly EntityDraftExtendedFieldSynchronizer $extendedFieldSynchronizer,
    ) {
    }

    #[\Override]
    public function supports(string $entityClass): bool
    {
        return $entityClass === OrderLineItem::class;
    }

    #[\Override]
    public function synchronizeFromDraft(EntityDraftAwareInterface $draft, EntityDraftAwareInterface $entity): void
    {
        assert($draft instanceof OrderLineItem);
        assert($entity instanceof OrderLineItem);

        if (!$entity->getId()) {
            $entity->addDraft($draft);
        }

        $this->synchronizeFields($draft, $entity);
    }

    #[\Override]
    public function synchronizeToDraft(EntityDraftAwareInterface $entity, EntityDraftAwareInterface $draft): void
    {
        assert($entity instanceof OrderLineItem);
        assert($draft instanceof OrderLineItem);

        $this->synchronizeFields($entity, $draft);

        $draft->setDraftSource($this->getReference($draft->getDraftSource()));
    }

    private function synchronizeFields(OrderLineItem $sourceLineItem, OrderLineItem $targetLineItem): void
    {
        $targetLineItem->setProduct($this->getReference($sourceLineItem->getProduct()));
        $targetLineItem->setParentProduct($this->getReference($sourceLineItem->getParentProduct()));
        $targetLineItem->setProductSku($sourceLineItem->getProductSku());
        $targetLineItem->setProductName($sourceLineItem->getProductName());
        $targetLineItem->setProductVariantFields($sourceLineItem->getProductVariantFields());
        $targetLineItem->setFreeFormProduct($sourceLineItem->getFreeFormProduct());
        $targetLineItem->setQuantity($sourceLineItem->getQuantity());
        $targetLineItem->setProductUnit($this->getReference($sourceLineItem->getProductUnit()));
        $targetLineItem->setProductUnitCode($sourceLineItem->getProductUnitCode());

        if ($sourceLineItem->getPrice()) {
            $targetLineItem->setPrice(clone $sourceLineItem->getPrice());
        } else {
            $targetLineItem->setPrice(null);
        }

        $targetLineItem->setPriceType($sourceLineItem->getPriceType());

        if ($sourceLineItem->getShipBy()) {
            $targetLineItem->setShipBy(clone $sourceLineItem->getShipBy());
        } else {
            $targetLineItem->setShipBy(null);
        }

        $targetLineItem->setFromExternalSource($sourceLineItem->isFromExternalSource());
        $targetLineItem->setComment($sourceLineItem->getComment());
        $targetLineItem->setShippingMethod($sourceLineItem->getShippingMethod());
        $targetLineItem->setShippingMethodType($sourceLineItem->getShippingMethodType());
        $targetLineItem->setShippingEstimateAmount($sourceLineItem->getShippingEstimateAmount());
        $targetLineItem->setChecksum($sourceLineItem->getChecksum());

        $this->syncKitItemLineItems($sourceLineItem, $targetLineItem);
    }

    private function syncKitItemLineItems(OrderLineItem $sourceLineItem, OrderLineItem $targetLineItem): void
    {
        /** @var Collection<OrderProductKitItemLineItem> $targetKitItemLineItems */
        $targetKitItemLineItems = $targetLineItem->getKitItemLineItems();
        /** @var Collection<OrderProductKitItemLineItem> $sourceKitItemLineItems */
        $sourceKitItemLineItems = $sourceLineItem->getKitItemLineItems();

        foreach ($targetKitItemLineItems as $kitItemId => $targetKitItemLineItem) {
            if (!$sourceKitItemLineItems->containsKey($kitItemId)) {
                $targetLineItem->removeKitItemLineItem($targetKitItemLineItem);
            }
        }

        foreach ($sourceKitItemLineItems as $kitItemId => $sourceKitItemLineItem) {
            if (!$targetKitItemLineItems->containsKey($kitItemId)) {
                $targetKitItemLineItem = $this->createSameInstance($sourceKitItemLineItem);

                $this->synchronizeKitItemLineItemFields($sourceKitItemLineItem, $targetKitItemLineItem);

                // Must be called after ::synchronizeKitItemLineItemFields() to ensure that kit item id is set.
                $targetLineItem->addKitItemLineItem($targetKitItemLineItem);
            } else {
                $targetKitItemLineItem = $targetKitItemLineItems->get($kitItemId);

                $this->synchronizeKitItemLineItemFields($sourceKitItemLineItem, $targetKitItemLineItem);
            }
        }
    }

    private function synchronizeKitItemLineItemFields(
        OrderProductKitItemLineItem $sourceKitItemLineItem,
        OrderProductKitItemLineItem $targetKitItemLineItem
    ): void {
        $kitItem = $sourceKitItemLineItem->getKitItem();
        $targetKitItemLineItem->setKitItem($this->getReference($kitItem));
        $targetKitItemLineItem->setKitItemId($sourceKitItemLineItem->getKitItemId());
        $targetKitItemLineItem->setKitItemLabel($sourceKitItemLineItem->getKitItemLabel());
        $targetKitItemLineItem->setOptional($sourceKitItemLineItem->isOptional());
        $targetKitItemLineItem->setMinimumQuantity($sourceKitItemLineItem->getMinimumQuantity());
        $targetKitItemLineItem->setMaximumQuantity($sourceKitItemLineItem->getMaximumQuantity());
        $targetKitItemLineItem->setSortOrder($sourceKitItemLineItem->getSortOrder());

        $product = $sourceKitItemLineItem->getProduct();
        $targetKitItemLineItem->setProduct($this->getReference($product));
        $targetKitItemLineItem->setProductId($sourceKitItemLineItem->getProductId());
        $targetKitItemLineItem->setProductSku($sourceKitItemLineItem->getProductSku());
        $targetKitItemLineItem->setProductName($sourceKitItemLineItem->getProductSku());
        $targetKitItemLineItem->setQuantity($sourceKitItemLineItem->getQuantity());

        $productUnit = $sourceKitItemLineItem->getProductUnit();
        $targetKitItemLineItem->setProductUnit($this->getReference($productUnit));
        $targetKitItemLineItem->setProductUnitCode($sourceKitItemLineItem->getProductUnitCode());
        $targetKitItemLineItem->setProductUnitPrecision($sourceKitItemLineItem->getProductUnitPrecision());

        if ($sourceKitItemLineItem->getPrice()) {
            $targetKitItemLineItem->setPrice(clone $sourceKitItemLineItem->getPrice());
        } else {
            $targetKitItemLineItem->setPrice(null);
        }

        $this->synchronizeKitItemLineItemExtendedFields($sourceKitItemLineItem, $targetKitItemLineItem);
    }

    private function synchronizeKitItemLineItemExtendedFields(
        OrderProductKitItemLineItem $sourceKitItemLineItem,
        OrderProductKitItemLineItem $targetKitItemLineItem
    ): void {
        $applicableExtendedFields = $this->extendedFieldsProvider
            ->getApplicableExtendedFields(OrderProductKitItemLineItem::class);
        foreach ($applicableExtendedFields as $fieldName => $fieldType) {
            $this->extendedFieldSynchronizer
                ->synchronize($sourceKitItemLineItem, $targetKitItemLineItem, $fieldName, $fieldType);
        }
    }

    private function getReference(?object $entity): ?object
    {
        return $this->draftSyncReferenceResolver->getReference($entity);
    }

    /**
     * Creates an instance of the same class as the specified entity is.
     */
    private function createSameInstance(object $object): object
    {
        return new (ClassUtils::getClass($object));
    }
}
