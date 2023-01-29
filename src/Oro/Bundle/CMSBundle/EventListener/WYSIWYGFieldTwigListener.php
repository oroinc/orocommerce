<?php

namespace Oro\Bundle\CMSBundle\EventListener;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\CMSBundle\Parser\TwigParser;
use Oro\Bundle\CMSBundle\WYSIWYG\WYSIWYGProcessedDTO;
use Oro\Bundle\CMSBundle\WYSIWYG\WYSIWYGProcessedEntityDTO;
use Oro\Bundle\CMSBundle\WYSIWYG\WYSIWYGTwigFunctionProcessorInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
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

    private ContainerInterface $container;
    /** @var string[][] */
    private array $fieldLists = [];
    private array $scheduled = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        if ($this->isApplicable($args)) {
            $isFlushNeeded = $this->processEntity($this->createDTO($args));
            $this->postProcess($args, $isFlushNeeded);
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        if ($this->isApplicable($args)) {
            $isFlushNeeded = $this->processEntity($this->createDTO($args));
            $this->postProcess($args, $isFlushNeeded);
        }
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        if ($this->isApplicable($args)) {
            $this->postProcess($args, $this->getTwigFunctionProcessor()->onPreRemove($this->createDTO($args)));
        }
    }

    private function isApplicable(LifecycleEventArgs $args): bool
    {
        if (!$this->enabled
            || !\is_object($args->getEntity())
            || $args->getEntity() instanceof AbstractLocalizedFallbackValue
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

    private function postProcess(LifecycleEventArgs $args, bool $isFlushNeeded): void
    {
        if (!$isFlushNeeded) {
            return;
        }

        $entityManager = $args->getEntityManager();
        $emHash = spl_object_hash($entityManager);
        if (!isset($this->scheduled[$emHash])) {
            $this->scheduled[$emHash] = [
                'em' => $entityManager,
                'entities' => [
                    'scheduleForInsert' => [],
                    'scheduleForUpdate' => [],
                    'scheduleForDelete' => [],
                ],
            ];
        }
    }

    /**
     * Saves entities which has been scheduled in unit of work during the latest flush for the later processing
     * as it is not allowed to call flush() in this case.
     *
     * @see https://github.com/doctrine/orm/issues/6292
     * @see https://github.com/doctrine/orm/issues/6024
     */
    public function postFlush(PostFlushEventArgs $args): void
    {
        $entityManager = $args->getEntityManager();
        $emHash = spl_object_hash($entityManager);
        if (!isset($this->scheduled[$emHash])) {
            return;
        }

        $unitOfWork = $entityManager->getUnitOfWork();
        $entities = [
            'scheduleForInsert' => $unitOfWork->getScheduledEntityInsertions(),
            'scheduleForUpdate' => $unitOfWork->getScheduledEntityUpdates(),
            'scheduleForDelete' => $unitOfWork->getScheduledEntityDeletions(),
        ];

        foreach ($this->scheduled[$emHash]['entities'] as $operation => $scheduledEntities) {
            $this->scheduled[$emHash]['entities'][$operation] = array_merge($scheduledEntities, $entities[$operation]);
        }
    }

    /**
     * Workaround for the case when flush() cannot be called inside postFlush(): performs all scheduled operations
     * at the end of execution.
     */
    public function onTerminate(): void
    {
        try {
            foreach ($this->scheduled as $managerWithScheduledEntities) {
                /** @var EntityManager $entityManager */
                $entityManager = $managerWithScheduledEntities['em'];
                $unitOfWork = $entityManager->getUnitOfWork();
                $doFlush = false;
                foreach ($managerWithScheduledEntities['entities'] as $operation => $scheduledEntities) {
                    foreach ($scheduledEntities as $entity) {
                        // Ensures entity is in identity map.
                        $entityManager->merge($entity);

                        $unitOfWork->$operation($entity);
                        $doFlush = true;
                    }
                }

                if ($doFlush) {
                    $entityManager->flush();
                }
            }
        } catch (\Throwable $throwable) {
            $this->getLogger()->error(
                sprintf(
                    'Failed to execute pending %s of %s - entity manager might has been untimely cleared',
                    strtolower(substr($operation, -6)),
                    $entityManager->getClassMetadata(get_class($entity))->getName()
                ),
                ['exception' => $throwable]
            );
        } finally {
            $this->scheduled = [];
        }
    }

    public function preClear(): void
    {
        $this->onTerminate();
    }

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
        $wysiwygFields = $this->getWysiwygFields($metadata);
        $foundTwigFunctionCalls = [[]];
        foreach ($collectionDTO->getEntity() as $entity) {
            foreach ($wysiwygFields as $fieldName => $fieldType) {
                if (!isset($applicableMapping[$fieldType])) {
                    continue;
                }

                $foundTwigFunctionCalls[][$fieldType] = $twigParser->findFunctionCalls(
                    $metadata->getFieldValue($entity, $fieldName),
                    $applicableMapping[$fieldType]
                );
            }
        }

        return $twigFunctionProcessor
            ->processTwigFunctions($processedDTO, array_merge_recursive(...$foundTwigFunctionCalls));
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
            if (isset($mapping['targetEntity']) &&
                is_a($mapping['targetEntity'], AbstractLocalizedFallbackValue::class, true)
            ) {
                $this->fieldLists[$entityName][$relationName] = $mapping['targetEntity'];
            }
        }
    }

    private function collectSerializedWysiwygFields(string $entityName, array $applicableFieldTypes): void
    {
        /** @var FieldConfigId $fieldConfigId */
        foreach ($this->getEntityConfigManager()->getIds('extend', $entityName, true) as $fieldConfigId) {
            $fieldType = $fieldConfigId->getFieldType();

            if (\in_array($fieldType, $applicableFieldTypes, true)) {
                $this->fieldLists[$entityName][$fieldConfigId->getFieldName()] = $fieldType;
            }
        }
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
            PropertyAccessorInterface::class,
            LoggerInterface::class
        ];
    }

    private function getTwigParser(): TwigParser
    {
        return $this->container->get('oro_cms.parser.twig');
    }

    private function getTwigFunctionProcessor(): WYSIWYGTwigFunctionProcessorInterface
    {
        return $this->container->get('oro_cms.wysiwyg.chain_twig_function_processor');
    }

    private function getEntityConfigManager(): EntityConfigManager
    {
        return $this->container->get(EntityConfigManager::class);
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        return $this->container->get(PropertyAccessorInterface::class);
    }

    private function getLogger(): LoggerInterface
    {
        return $this->container->get(LoggerInterface::class);
    }
}
