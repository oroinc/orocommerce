<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Strategy;

use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
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
            $this->context->addPostponedRow($this->context->getValue('rawItemData'));
            $this->context->setValue('postponedRowsDelay', 0);

            return null;
        }

        $data = $this->context->getValue('itemData');

        if (array_key_exists('additionalUnitPrecisions', $data)) {
            $data['unitPrecisions'] = $data['additionalUnitPrecisions'];
            unset($data['additionalUnitPrecisions']);
        }

        $this->context->setValue('itemData', $data);
        $event = new ProductStrategyEvent($entity, $this->context->getValue('itemData'));
        $this->eventDispatcher->dispatch($event, ProductStrategyEvent::PROCESS_BEFORE);

        return parent::beforeProcessEntity($entity);
    }

    /**
     * @param Product $entity
     * {@inheritdoc}
     */
    protected function afterProcessEntity($entity)
    {
        $this->populateOwner($entity);

        $event = new ProductStrategyEvent($entity, $this->context->getValue('itemData'));
        $this->eventDispatcher->dispatch($event, ProductStrategyEvent::PROCESS_AFTER);

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

        if (!$searchContext && in_array($fieldName, ['primaryUnitPrecision', 'unitPrecisions'], true)) {
            $searchContext = ['product' => $entity];
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
}
