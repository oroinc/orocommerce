<?php

namespace Oro\Bundle\CMSBundle\EventListener;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\CMSBundle\Parser\TwigParser;
use Oro\Bundle\CMSBundle\WYSIWYG\WYSIWYGProcessedDTO;
use Oro\Bundle\CMSBundle\WYSIWYG\WYSIWYGProcessedEntityDTO;
use Oro\Bundle\CMSBundle\WYSIWYG\WYSIWYGTwigFunctionProcessorInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Listens to changes in wysiwyg fields to
 * 1) create children files for DAM assets used via wysiwyg_file() and wysiwyg_image() twig functions
 * 2) track usages of content widgets
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class WYSIWYGFieldTwigListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    /** @var TwigParser */
    private $twigParser;

    /** @var WYSIWYGTwigFunctionProcessorInterface */
    private $processor;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var string[][] */
    private $fieldLists = [];

    /** @var bool */
    private $transactional = false;

    /** @var EntityConfigManager */
    private $entityConfigManager;

    /**
     * @param EntityConfigManager $entityConfigManager
     * @param TwigParser $twigParser
     * @param WYSIWYGTwigFunctionProcessorInterface $processor
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(
        EntityConfigManager $entityConfigManager,
        TwigParser $twigParser,
        WYSIWYGTwigFunctionProcessorInterface $processor,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->entityConfigManager = $entityConfigManager;
        $this->twigParser = $twigParser;
        $this->processor = $processor;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        if ($this->isApplicable($args)) {
            $isFlushNeeded = $this->processEntity($this->createDTO($args));
            $this->postProcess($args, $isFlushNeeded);
        }
    }

    /**
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        if ($this->isApplicable($args)) {
            $isFlushNeeded = $this->processEntity($this->createDTO($args));
            $this->postProcess($args, $isFlushNeeded);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args): void
    {
        if ($this->isApplicable($args)) {
            $isFlushNeeded = $this->processor->onPreRemove($this->createDTO($args));
            $this->postProcess($args, $isFlushNeeded);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     * @return bool
     */
    private function isApplicable(LifecycleEventArgs $args): bool
    {
        if (!$this->enabled
            || !$this->processor->getApplicableMapping()
            || !is_object($args->getEntity())
            || $args->getEntity() instanceof LocalizedFallbackValue
        ) {
            return false;
        }

        $metadata = $args->getEntityManager()->getClassMetadata(\get_class($args->getEntity()));
        if (\count($metadata->getIdentifier()) !== 1) {
            // Composite keys are not supported.
            return false;
        }

        return !empty($this->getWysiwygFields($metadata));
    }

    /**
     * @param LifecycleEventArgs $args
     * @param bool $isFlushNeeded
     */
    private function postProcess(LifecycleEventArgs $args, bool $isFlushNeeded): void
    {
        if ($isFlushNeeded && !$this->transactional) {
            $args->getEntityManager()->beginTransaction();
            $this->transactional = true;
        }
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->transactional) {
            $this->transactional = false;
            $args->getEntityManager()->flush();
            $args->getEntityManager()->commit();
        }
    }

    /**
     * @param LifecycleEventArgs $args
     * @return WYSIWYGProcessedDTO
     */
    private function createDTO(LifecycleEventArgs $args): WYSIWYGProcessedDTO
    {
        return new WYSIWYGProcessedDTO(
            new WYSIWYGProcessedEntityDTO(
                $args->getEntityManager(),
                $this->propertyAccessor,
                $args->getEntity(),
                $args instanceof PreUpdateEventArgs ? $args->getEntityChangeSet() : null
            )
        );
    }

    /**
     * @param WYSIWYGProcessedDTO $processedDTO
     * @return bool
     */
    private function processEntity(WYSIWYGProcessedDTO $processedDTO): bool
    {
        $isFlushNeeded = false;

        $wysiwygFields = $this->getWysiwygFields($processedDTO->getProcessedEntity()->getMetadata());
        foreach ($wysiwygFields as $fieldName => $fieldType) {
            $processedFieldDTO = $processedDTO->withProcessedEntityField($fieldName, $fieldType);

            if ($processedFieldDTO->getProcessedEntity()->isRelation()) {
                $isFlushNeeded = $this->processRelation($processedFieldDTO) || $isFlushNeeded;
            } else {
                $isFlushNeeded = $this->processField($processedFieldDTO) || $isFlushNeeded;
            }
        }

        return $isFlushNeeded;
    }

    /**
     * @param WYSIWYGProcessedDTO $processedDTO
     * @return bool
     */
    private function processField(WYSIWYGProcessedDTO $processedDTO): bool
    {
        $processedEntity = $processedDTO->getProcessedEntity();
        if (!$processedEntity->isFieldChanged()) {
            return false;
        }

        $applicableMapping = $this->processor->getApplicableMapping();
        if (!isset($applicableMapping[$processedEntity->getFieldType()])) {
            return false;
        }

        $foundTwigFunctionCalls[$processedEntity->getFieldType()] = $this->twigParser->findFunctionCalls(
            $processedEntity->getFieldValue(),
            $applicableMapping[$processedEntity->getFieldType()]
        );

        return $this->processor->processTwigFunctions($processedDTO, $foundTwigFunctionCalls);
    }

    /**
     * @param WYSIWYGProcessedDTO $processedDTO
     * @return bool
     */
    private function processRelation(WYSIWYGProcessedDTO $processedDTO): bool
    {
        $processedEntity = $processedDTO->getProcessedEntity();
        $collection = $processedEntity->getFieldValue();

        if (!$this->isScheduledCollection($processedEntity, $collection)) {
            return false;
        }

        $collectionDTO = new WYSIWYGProcessedEntityDTO(
            $processedEntity->getEntityManager(),
            $this->propertyAccessor,
            $collection
        );
        $metadata = $collectionDTO->getMetadata();

        $applicableMapping = $this->processor->getApplicableMapping();
        $foundTwigFunctionCalls = [];

        foreach ($this->getWysiwygFields($metadata) as $fieldName => $fieldType) {
            if (!isset($applicableMapping[$fieldType])) {
                continue;
            }

            $foundTwigFunctionCalls[$fieldType] = [];
            foreach ($collectionDTO->getEntity() as $entity) {
                $foundCalls = $this->twigParser->findFunctionCalls(
                    $metadata->getFieldValue($entity, $fieldName),
                    $applicableMapping[$fieldType]
                );

                if ($foundCalls) {
                    $foundTwigFunctionCalls[$fieldType] = \array_merge_recursive(
                        $foundTwigFunctionCalls[$fieldType],
                        $foundCalls
                    );
                }
            }
        }

        return $this->processor->processTwigFunctions(
            $processedDTO->withProcessedEntityField($fieldName, $fieldType),
            $foundTwigFunctionCalls
        );
    }

    /**
     * @param WYSIWYGProcessedEntityDTO $processedEntity
     * @param iterable $collection
     * @return bool
     */
    private function isScheduledCollection(WYSIWYGProcessedEntityDTO $processedEntity, $collection): bool
    {
        if (!$collection instanceof Collection

            // No sense check non-initialized collections
            || ($collection instanceof AbstractLazyCollection && !$collection->isInitialized())

            // All LocalizedFallbackValue removed only in case when removed parent entity.
            // It makes no sense to handle this case in collection.
            || !\count($collection)
        ) {
            return false;
        }

        $uow = $processedEntity->getEntityManager()->getUnitOfWork();
        $collectionUpdates = $uow->getScheduledCollectionUpdates();
        if (isset($collectionUpdates[\spl_object_hash($collection)])) {
            return true;
        }

        foreach ($collection as $entity) {
            if ($uow->isEntityScheduled($entity) || $uow->getEntityChangeSet($entity)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ClassMetadata $metadata
     * @return array
     */
    private function getWysiwygFields(ClassMetadata $metadata): array
    {
        $entityName = $metadata->getName();
        if (!isset($this->fieldLists[$entityName])) {
            $this->fieldLists[$entityName] = [];
            $applicableFieldTypes = \array_keys($this->processor->getApplicableMapping());

            $this->collectRegularWysiwygFields($metadata, $applicableFieldTypes);
            $this->collectSerializedWysiwygFields($metadata->getName(), $applicableFieldTypes);
        }

        return $this->fieldLists[$entityName];
    }

    /**
     * @param ClassMetadata $metadata
     * @param array $applicableFieldTypes
     */
    private function collectRegularWysiwygFields(ClassMetadata $metadata, array $applicableFieldTypes): void
    {
        $entityName = $metadata->getName();

        foreach ($metadata->getFieldNames() as $fieldName) {
            $mapping = $metadata->getFieldMapping($fieldName);
            if (\in_array($mapping['type'], $applicableFieldTypes, true)) {
                $this->fieldLists[$entityName][$fieldName] = $mapping['type'];
            }
        }

        foreach ($metadata->getAssociationMappings() as $relationName => $mapping) {
            if (isset($mapping['targetEntity']) && $mapping['targetEntity'] === LocalizedFallbackValue::class) {
                $this->fieldLists[$entityName][$relationName] = LocalizedFallbackValue::class;
            }
        }
    }

    /**
     * @param string $entityName
     * @param array $applicableFieldTypes
     */
    private function collectSerializedWysiwygFields(string $entityName, array $applicableFieldTypes): void
    {
        $entityConfigModel = $this->entityConfigManager->getConfigEntityModel($entityName);
        if ($entityConfigModel) {
            // Working with fieldConfigModels because regular doctrine metadata does not contain info about
            // serialized fields.
            foreach ($entityConfigModel->getFields() as $fieldConfigModel) {
                $fieldName = $fieldConfigModel->getFieldName();
                $fieldType = $fieldConfigModel->getType();

                if (\in_array($fieldType, $applicableFieldTypes, true)) {
                    $this->fieldLists[$entityName][$fieldName] = $fieldType;
                }
            }
        }
    }
}
