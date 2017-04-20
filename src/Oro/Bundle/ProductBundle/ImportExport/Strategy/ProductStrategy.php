<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Strategy;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductStrategyEvent;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

class ProductStrategy extends LocalizedFallbackValueAwareStrategy implements ClosableInterface
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var BusinessUnit
     */
    protected $owner;

    /**
     * @var string
     */
    protected $variantLinkClass;

    /**
     * @var string
     */
    protected $productClass;

    /**
     * @var array|Product[]
     */
    protected $processedProducts = [];

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->processedProducts = [];
    }

    /**
     * @param SecurityFacade $securityFacade
     */
    public function setSecurityFacade($securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param string $variantLinkClass
     */
    public function setVariantLinkClass($variantLinkClass)
    {
        $this->variantLinkClass = $variantLinkClass;
    }

    /**
     * @param string $productClass
     */
    public function setProductClass($productClass)
    {
        $this->productClass = $productClass;
    }

    /**
     * @param Product $entity
     * @return Product
     */
    protected function beforeProcessEntity($entity)
    {
        $data = $this->context->getValue('itemData');

        if (array_key_exists('additionalUnitPrecisions', $data)) {
            $data['unitPrecisions'] = $data['additionalUnitPrecisions'];
            unset($data['additionalUnitPrecisions']);
        }

        $this->context->setValue('itemData', $data);
        $event = new ProductStrategyEvent($entity, $this->context->getValue('itemData'));
        $this->eventDispatcher->dispatch(ProductStrategyEvent::PROCESS_BEFORE, $event);

        return parent::beforeProcessEntity($entity);
    }

    /**
     * @param Product $entity
     * @return Product
     */
    protected function afterProcessEntity($entity)
    {
        $this->populateOwner($entity);

        $event = new ProductStrategyEvent($entity, $this->context->getValue('itemData'));
        $this->eventDispatcher->dispatch(ProductStrategyEvent::PROCESS_AFTER, $event);

        /** @var Product $entity */
        $entity = parent::afterProcessEntity($entity);
        $this->processedProducts[$entity->getSku()] = $entity;

        return $entity;
    }

    /**
     * @param Product $entity
     */
    protected function populateOwner(Product $entity)
    {
        if (false === $this->owner) {
            return;
        }

        if ($this->owner) {
            $entity->setOwner($this->owner);

            return;
        }

        /** @var User $user */
        $user = $this->securityFacade->getLoggedUser();
        if (!$user) {
            $this->owner = false;

            return;
        }

        $this->owner = $this->databaseHelper->getEntityReference($user->getOwner());

        $entity->setOwner($this->owner);
    }

    /**
     * {@inheritdoc}
     */
    protected function findEntityByIdentityValues($entityName, array $identityValues)
    {
        if (is_a($entityName, $this->variantLinkClass, true)) {
            $newIdentityValues = [];
            foreach ($identityValues as $entityFieldName => $entity) {
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
        }

        return parent::findEntityByIdentityValues($entityName, $identityValues);
    }


    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @param object $entity
     * @param array|null $itemData
     */
    protected function updateRelations($entity, array $itemData = null)
    {
        $entityName = ClassUtils::getClass($entity);
        $fields     = $this->fieldHelper->getFields($entityName, true);

        foreach ($fields as $field) {
            if ($this->fieldHelper->isRelation($field)) {
                $fieldName         = $field['name'];
                $isFullRelation    = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'full', false);
                $isPersistRelation = $this->databaseHelper->isCascadePersist($entityName, $fieldName);
                $inversedFieldName = $this->getInversedFieldName($entityName, $fieldName);

                // additional search parameters to find only related entities
                $searchContext = [];
                if ($inversedFieldName) {
                    $searchContext[$inversedFieldName] = $entity;
                }

                if ($this->fieldHelper->isSingleRelation($field)) {
                    // single relation
                    $relationEntity = $this->fieldHelper->getObjectValue($entity, $fieldName);
                    if ($relationEntity) {
                        $relationItemData = $this->fieldHelper->getItemData($itemData, $fieldName);
                        $relationEntity   = $this->processEntity(
                            $relationEntity,
                            $isFullRelation,
                            $isPersistRelation,
                            $relationItemData,
                            $searchContext,
                            true
                        );
                    }
                    $this->fieldHelper->setObjectValue($entity, $fieldName, $relationEntity);
                } elseif ($this->fieldHelper->isMultipleRelation($field)) {
                    // multiple relation
                    $relationCollection = $this->fieldHelper->getObjectValue($entity, $fieldName);
                    if ($relationCollection instanceof Collection) {
                        $collectionItemData = $this->fieldHelper->getItemData($itemData, $fieldName);
                        $collectionEntities = new ArrayCollection();

                        foreach ($relationCollection as $collectionEntity) {
                            $entityItemData   = $this->fieldHelper->getItemData(array_shift($collectionItemData));
                            $collectionEntity = $this->processEntity(
                                $collectionEntity,
                                $isFullRelation,
                                $isPersistRelation,
                                $entityItemData,
                                $searchContext
                            );

                            if ($collectionEntity) {
                                $collectionEntities->add($collectionEntity);
                            }
                        }

                        $relationCollection->clear();
                        $this->fieldHelper->setObjectValue($entity, $fieldName, $collectionEntities);
                    }
                }
            }
        }
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
        if ($entity instanceof Product && array_key_exists($entity->getSku(), $this->processedProducts)) {
            return $this->processedProducts[$entity->getSku()];
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
    protected function getInversedFieldName($entityName, $fieldName)
    {
        $inversedFieldName = $this->databaseHelper->getInversedRelationFieldName($entityName, $fieldName);
        if ($inversedFieldName && $this->databaseHelper->isCascadePersist($entityName, $fieldName)
            && $this->databaseHelper->isSingleInversedRelation($entityName, $fieldName)
        ) {
            return $inversedFieldName;
        } elseif (!$inversedFieldName && $fieldName === 'primaryUnitPrecision') {
            return 'product';
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function importExistingEntity($entity, $existingEntity, $itemData = null, array $excludedFields = [])
    {
        if (is_a($entity, $this->productClass)) {
            $excludedFields[] = 'type';
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
                $this->context->incrementErrorEntriesCount();
                $this->strategyHelper->addValidationErrors([$error], $this->context);

                $this->doctrineHelper->getEntityManager($entity)->detach($entity);

                return null;
            }

            $usedCodes[] = $code;
        }

        return parent::validateAndUpdateContext($entity);
    }
}
