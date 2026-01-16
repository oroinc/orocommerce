<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Strategy;

use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductKitItemRepository;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductStrategyEvent;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Product import strategy.
 * In addition to Configurable strategy logic handles import of product unit precisions and variants.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ProductStrategy extends LocalizedFallbackValueAwareStrategy implements ClosableInterface
{
    /**
     * @var TokenAccessorInterface
     */
    protected $tokenAccessor;

    /**
     * @var int
     */
    protected $owner;

    /**
     * @var string
     */
    protected $variantLinkClass;

    /**
     * @var array|Product[]
     */
    protected $processedProducts = [];

    /**
     * @var array|ProductVariantLink[]
     */
    protected $processedVariantLinks = [];

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->processedProducts = [];
    }

    /**
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function setTokenAccessor($tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * @param string $variantLinkClass
     */
    public function setVariantLinkClass($variantLinkClass)
    {
        $this->variantLinkClass = $variantLinkClass;
    }

    /**
     * @param Product $entity
     * {@inheritdoc}
     */
    protected function beforeProcessEntity($entity)
    {
        $this->processedVariantLinks = [];
        // Postpone configurable products processing after simple ones
        // incremented_read option is set during postponed rows processing
        if (!$this->context->hasOption('incremented_read') && $entity->getType() === Product::TYPE_CONFIGURABLE) {
            $this->addPostponedRow();
            return null;
        }

        // Postpone rows with missing product kit item products
        if ($entity->getType() === Product::TYPE_KIT && $this->isKitPostponedRow($entity)) {
            $this->addPostponedRow();
            return null;
        }

        $data = $this->context->getValue('itemData');

        if (array_key_exists('additionalUnitPrecisions', $data)) {
            $data['unitPrecisions'] = $data['additionalUnitPrecisions'];
            unset($data['additionalUnitPrecisions']);
        }

        $this->context->setValue('itemData', $data);
        $event = new ProductStrategyEvent($entity, $this->context->getValue('itemData'));
        $event->setContext($this->context);
        $this->eventDispatcher->dispatch($event, ProductStrategyEvent::PROCESS_BEFORE);

        return parent::beforeProcessEntity($entity);
    }

    protected function addPostponedRow(): void
    {
        $this->context->addPostponedRow($this->context->getValue('rawItemData'));
        $this->context->setValue('postponedRowsDelay', 0);
    }

    /**
     * @param ?Product $entity returns null if the product is invalid
     * {@inheritdoc}
     */
    protected function afterProcessEntity($entity)
    {
        $this->populateOwner($entity);

        $event = new ProductStrategyEvent($entity, $this->context->getValue('itemData'));
        $event->setContext($this->context);
        $this->eventDispatcher->dispatch($event, ProductStrategyEvent::PROCESS_AFTER);

        // Check if the event listeners marked the product as invalid.
        if (!$event->isProductValid()) {
            return null;
        }

        /** @var Product $entity */
        $entity = parent::afterProcessEntity($entity);
        if ($entity) {
            // Clear unitPrecision collection items with unit null
            $productUnitPrecisions = $entity->getUnitPrecisions();
            foreach ($productUnitPrecisions as $unitPrecision) {
                if (!$unitPrecision->getProductUnitCode()) {
                    $productUnitPrecisions->removeElement($unitPrecision);
                }
            }
        }

        if ($entity && $entity->getSkuUppercase()) {
            $this->processedProducts[$entity->getSkuUppercase()] = $entity;
        }

        return $entity;
    }

    protected function populateOwner(Product $entity)
    {
        if (false === $this->owner) {
            return;
        }

        if ($this->owner) {
            $entity->setOwner($this->doctrineHelper->getEntityReference(BusinessUnit::class, $this->owner));

            return;
        }

        /** @var User $user */
        $user = $this->tokenAccessor->getUser();
        if (!$user) {
            $this->owner = false;

            return;
        }

        $this->owner = $user->getOwner()->getId();

        $entity->setOwner($this->doctrineHelper->getEntityReference(BusinessUnit::class, $this->owner));
    }

    /**
     * {@inheritdoc}
     */
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        // Ensures that the found ProductKitItem belongs to the same organization as Product.
        if ($entity instanceof ProductKitItem && $entity->getId() && $this->processingEntity?->getOrganization()) {
            return $this->getProductKitItemRepository()
                ->getProductKitItemByIdAndOrganization(
                    $entity->getId(),
                    $this->processingEntity->getOrganization()->getId()
                );
        }

        return parent::findExistingEntity($entity, $searchContext);
    }

    /**
     * {@inheritdoc}
     */
    protected function findEntityByIdentityValues($entityName, array $identityValues)
    {
        if (is_a($entityName, $this->variantLinkClass, true)) {
            $newIdentityValues = [];
            foreach ($identityValues as $entityFieldName => $entity) {
                if (null === $entity || '' === $entity) {
                    continue;
                }

                if ($this->databaseHelper->getIdentifier($entity)) {
                    $newIdentityValues[$entityFieldName] = $entity;
                } else {
                    $existingEntity = $this->findExistingEntity($entity);

                    if (!$existingEntity) {
                        return null;
                    }

                    $newIdentityValues[$entityFieldName] = $existingEntity;
                }
            }
            $identityValues = $newIdentityValues;
            if (empty($identityValues['parentProduct']) || empty($identityValues['product'])) {
                return null;
            }
        }

        return parent::findEntityByIdentityValues($entityName, $identityValues);
    }

    /**
     * {@inheritdoc}
     */
    protected function generateSearchContextForRelationsUpdate($entity, $entityName, $fieldName, $isPersistRelation)
    {
        $searchContext = parent::generateSearchContextForRelationsUpdate(
            $entity,
            $entityName,
            $fieldName,
            $isPersistRelation
        );

        if (!$searchContext) {
            if (in_array($fieldName, ['primaryUnitPrecision', 'unitPrecisions'], true)) {
                $searchContext = ['product' => $entity];
            } elseif ($entity instanceof ProductKitItem && $fieldName === 'kitItemProducts') {
                // Ensures that the ProductKitItem::$kitItemProducts collection is not totally recreated. Instead,
                // already existing ProductKitItemProduct entities are found using search context with the kitItem
                // specified here and a product that will be added as an identity field of ProductKitItemProduct in
                // AbstractImportStrategy::findExistingEntityByIdentityFields
                $searchContext = ['kitItem' => $entity];
            }
        }

        return $searchContext;
    }

    /**
     * {@inheritdoc}
     */
    protected function processEntity(
        $entity,
        $isFullData = false,
        $isPersistNew = false,
        $itemData = null,
        array $searchContext = [],
        $entityIsRelation = false
    ) {
        if ($entity instanceof Product && array_key_exists($entity->getSkuUppercase(), $this->processedProducts)) {
            return $this->processedProducts[$entity->getSkuUppercase()];
        }

        // Create new kit items by ignoring ID when the not existing product kit.
        if ($entity instanceof ProductKitItem && $entity->getId() && !$entity->getProductKit()?->getId()) {
            $this->fieldHelper->setObjectValue($entity, 'id', null);
        }

        return parent::processEntity($entity, $isFullData, $isPersistNew, $itemData, $searchContext, $entityIsRelation);
    }

    /**
     * Get additional search parameter name to find only related entities
     *
     * @param string $entityName
     * @param string $fieldName
     * @return string|null
     */
    protected function getInvertedFieldName($entityName, $fieldName)
    {
        $inversedFieldName = $this->databaseHelper->getInversedRelationFieldName($entityName, $fieldName);

        if ($inversedFieldName && $this->databaseHelper->isCascadePersist($entityName, $fieldName)
            && $this->databaseHelper->isSingleInversedRelation($entityName, $fieldName)
        ) {
            return $inversedFieldName;
        }

        if (!$inversedFieldName && $fieldName === 'primaryUnitPrecision') {
            return 'product';
        }

        return null;
    }

    /**
     * @param Product $entity
     * @param Product $existingEntity
     * {@inheritdoc}
     */
    protected function importExistingEntity($entity, $existingEntity, $itemData = null, array $excludedFields = [])
    {
        if ($entity instanceof Product) {
            $excludedFields[] = 'type';
            if ($entity->getType() === Product::TYPE_SIMPLE) {
                $excludedFields[] = 'variantLinks';
            } else {
                $excludedFields[] = 'parentVariantLinks';
            }

            // Add primary unit precision to unit precisions list if it was unintentionally removed
            $primaryUnitPrecision = $existingEntity->getPrimaryUnitPrecision();
            if ($primaryUnitPrecision
                && $primaryUnitPrecision->getProductUnitCode()
                && !$entity->getUnitPrecisions()->contains($primaryUnitPrecision)
            ) {
                $entity->addUnitPrecision($primaryUnitPrecision);
            }
        } elseif ($entity instanceof EntityFieldFallbackValue) {
            $this->strategyHelper->importEntity($existingEntity, $entity, ['id']);
        }

        parent::importExistingEntity($entity, $existingEntity, $itemData, $excludedFields);
    }

    /**
     * {@inheritdoc}
     */
    protected function importEntityFields($entity, $existingEntity, $isFullData, $entityIsRelation, $itemData)
    {
        // Ensures that ProductKitItem which does not belong to Product will not be changed.
        if ($existingEntity instanceof ProductKitItem &&
            $this->processingEntity->getId() !== $existingEntity->getProductKit()->getId()
        ) {
            return $existingEntity;
        }

        return parent::importEntityFields($entity, $existingEntity, $isFullData, $entityIsRelation, $itemData);
    }

    /**
     * Validate unitPrecisions array data before model validation because model merges same codes
     * {@inheritdoc}
     */
    protected function validateAndUpdateContext($entity)
    {
        $itemData = $this->context->getValue('itemData');
        $unitPrecisions = [];

        if (isset($itemData['unitPrecisions'])) {
            $unitPrecisions = $itemData['unitPrecisions'];
        }

        if (isset($itemData['primaryUnitPrecision'])) {
            $unitPrecisions[] = $itemData['primaryUnitPrecision'];
        }

        $usedCodes = [];

        foreach ($unitPrecisions as $unitPrecision) {
            if (!isset($unitPrecision['unit']['code'])) {
                continue;
            }

            $code = $unitPrecision['unit']['code'];

            if (in_array($code, $usedCodes, true)) {
                $error = $this->translator->trans('oro.product.productunitprecision.duplicate_units_import_error');
                $this->processValidationErrors($entity, [$error]);

                return null;
            }

            $usedCodes[] = $code;
        }

        $validationErrors = $this->strategyHelper->validateEntity($entity, null, ['Import']);
        if ($validationErrors) {
            $this->processValidationErrors($entity, $validationErrors);

            return null;
        }

        return parent::validateAndUpdateContext($entity);
    }

    /**
     * Ensures ProductKitItem::$kitItemProducts and ProductKitItem::$labels are reverted when product entity
     * is invalided.
     */
    protected function invalidateEntity($entity)
    {
        if ($entity->isKit()) {
            $kitItemsCollection = $entity->getKitItems();
            // Ensures that ProductKitItem related entities are detached when a product is invalidated and detached.
            if ($kitItemsCollection instanceof PersistentCollection) {
                foreach ($kitItemsCollection->getSnapshot() as $kitItem) {
                    $this->relatedEntityStateHelper->rememberAlteredCollectionsItems($kitItem);
                }
            }
        }

        parent::invalidateEntity($entity);
    }

    /**
     * {@inheritDoc}
     */
    protected function processValidationErrors($entity, array $validationErrors)
    {
        parent::processValidationErrors($entity, $validationErrors);

        // Remove variant link from parentVariantLinks collection.
        // Variant Links are added to configurable product is also added to parentVariantLinks collection of it`s simple
        // During flush such variant links are added to scheduled insertions and fails flush of validation failed.
        foreach ($this->processedVariantLinks as $variantLink) {
            if (!$variantLink->getProduct()) {
                continue;
            }
            $variantLink->getProduct()
                ->getParentVariantLinks()
                ->removeElement($variantLink);
        }
        $this->processedVariantLinks = [];
    }

    /**
     * {@inheritDoc}
     */
    protected function cacheInverseFieldRelation($entityName, $fieldName, $relationEntity)
    {
        parent::cacheInverseFieldRelation($entityName, $fieldName, $relationEntity);

        if ($relationEntity instanceof ProductVariantLink) {
            $this->processedVariantLinks[] = $relationEntity;
        }
    }

    /**
     * Checks that all Kit Item Products exist in the ORO system.
     */
    protected function isKitPostponedRow(Product $entity): bool
    {
        $kitItemSkus = [];
        foreach ($entity->getKitItems() as $kitItem) {
            $kitItemSkus[] = array_map(
                static fn ($item) => $item?->getProduct()?->getSkuUppercase(),
                $kitItem->getKitItemProducts()->toArray()
            );
        }

        $kitItemSkus = array_unique(array_filter(array_merge(...$kitItemSkus)));
        if (!$kitItemSkus) {
            return false;
        }

        $skus = [];
        foreach ($this->processedProducts as $sku => $product) {
            if (in_array($sku, $kitItemSkus, true)) {
                $skus[] = $sku;
            }
        }

        $result = array_map(static fn ($item) => $item['sku'], $this->getProductSkusBySkus($kitItemSkus));
        $skus = array_unique(array_merge($skus, $result));

        return count($kitItemSkus) > count($skus);
    }

    protected function getProductSkusBySkus(array $skus): array
    {
        return $this->getProductRepository()
            ->getBySkuQueryBuilder($skus)
            ->select('product.skuUppercase as sku')
            ->getQuery()
            ->getResult();
    }

    protected function getProductRepository(): ProductRepository
    {
        return $this->doctrineHelper->getEntityRepository(Product::class);
    }

    protected function getProductKitItemRepository(): ProductKitItemRepository
    {
        return $this->doctrineHelper->getEntityRepository(ProductKitItem::class);
    }
}
