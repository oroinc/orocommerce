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
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Listens to changes in wysiwyg fields to
 * 1) create children files for DAM assets used via wysiwyg_file() and wysiwyg_image() twig functions
 * 2) track usages of content widgets
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class WYSIWYGFieldTwigListener implements OptionalListenerInterface, ServiceSubscriberInterface
{
    use OptionalListenerTrait;

    /** @var ContainerInterface */
    private $container;

    /** @var string[][] */
    private $fieldLists = [];

    /** @var bool */
    private $transactional = false;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_cms.parser.twig' => TwigParser::class,
            'oro_cms.wysiwyg.chain_twig_function_processor' => WYSIWYGTwigFunctionProcessorInterface::class,
            EntityConfigManager::class,
            PropertyAccessorInterface::class
        ];
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
            $this->postProcess($args, $this->getTwigFunctionProcessor()->onPreRemove($this->createDTO($args)));
        }
    }

    /**
     * @param LifecycleEventArgs $args
     * @return bool
     */
    private function isApplicable(LifecycleEventArgs $args): bool
    {
        if (!$this->enabled
            || !\is_object($args->getEntity())
            || $args->getEntity() instanceof LocalizedFallbackValue
            || !$this->getTwigFunctionProcessor()->getApplicableMapping()
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
                $this->getPropertyAccessor(),
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

        $twigFunctionProcessor = $this->getTwigFunctionProcessor();
        $applicableMapping = $twigFunctionProcessor->getApplicableMapping();
        if (!isset($applicableMapping[$processedEntity->getFieldType()])) {
            return false;
        }

        $foundTwigFunctionCalls[$processedEntity->getFieldType()] = $this->getTwigParser()->findFunctionCalls(
            $processedEntity->getFieldValue(),
            $applicableMapping[$processedEntity->getFieldType()]
        );

        return $twigFunctionProcessor->processTwigFunctions($processedDTO, $foundTwigFunctionCalls);
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
            $this->getPropertyAccessor(),
            $collection
        );
        $metadata = $collectionDTO->getMetadata();

        $twigParser = $this->getTwigParser();
        $twigFunctionProcessor = $this->getTwigFunctionProcessor();
        $applicableMapping = $twigFunctionProcessor->getApplicableMapping();
        $isFlushNeeded = false;

        foreach ($this->getWysiwygFields($metadata) as $fieldName => $fieldType) {
            if (!isset($applicableMapping[$fieldType])) {
                continue;
            }

            $foundTwigFunctionCalls = [$fieldType => []];
            foreach ($collectionDTO->getEntity() as $entity) {
                $foundCalls = $twigParser->findFunctionCalls(
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

            $isFlushNeeded = $twigFunctionProcessor->processTwigFunctions(
                $processedDTO->withProcessedEntityField($fieldName, $fieldType),
                $foundTwigFunctionCalls
            ) || $isFlushNeeded;
        }

        return $isFlushNeeded;
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
            $applicableFieldTypes = \array_keys($this->getTwigFunctionProcessor()->getApplicableMapping());

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
        $entityConfigModel = $this->getEntityConfigManager()->getConfigEntityModel($entityName);
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

    /**
     * @return TwigParser
     */
    private function getTwigParser(): TwigParser
    {
        return $this->container->get('oro_cms.parser.twig');
    }

    /**
     * @return WYSIWYGTwigFunctionProcessorInterface
     */
    private function getTwigFunctionProcessor(): WYSIWYGTwigFunctionProcessorInterface
    {
        return $this->container->get('oro_cms.wysiwyg.chain_twig_function_processor');
    }

    /**
     * @return EntityConfigManager
     */
    private function getEntityConfigManager(): EntityConfigManager
    {
        return $this->container->get(EntityConfigManager::class);
    }

    /**
     * @return PropertyAccessorInterface
     */
    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        return $this->container->get(PropertyAccessorInterface::class);
    }
}
