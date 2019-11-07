<?php

namespace Oro\Bundle\CMSBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CMSBundle\Parser\TwigParser;
use Oro\Bundle\CMSBundle\WYSIWYG\WYSIWYGProcessedDTO;
use Oro\Bundle\CMSBundle\WYSIWYG\WYSIWYGProcessedEntityDTO;
use Oro\Bundle\CMSBundle\WYSIWYG\WYSIWYGTwigFunctionProcessorInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;

/**
 * Listens to changes in wysiwyg fields to
 * 1) create children files for DAM assets used via wysiwyg_file() and wysiwyg_image() twig functions
 * 2) track usages of DAM assets
 */
class WYSIWYGFieldTwigListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    /** @var TwigParser */
    private $twigParser;

    /** @var WYSIWYGTwigFunctionProcessorInterface */
    private $processor;

    /** @var string[][] */
    private $fieldLists = [];

    /** @var bool */
    private $transactional = false;

    /**
     * @param TwigParser $twigParser
     * @param WYSIWYGTwigFunctionProcessorInterface $processor
     */
    public function __construct(TwigParser $twigParser, WYSIWYGTwigFunctionProcessorInterface $processor)
    {
        $this->twigParser = $twigParser;
        $this->processor = $processor;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        $processedDTO = $this->prepareDTO($args);
        if ($processedDTO) {
            $isFlushNeeded = $this->processChangedEntity($processedDTO);
            $this->postProcess($args, $isFlushNeeded);
        }
    }

    /**
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $processedDTO = $this->prepareDTO($args);
        if ($processedDTO) {
            $isFlushNeeded = $this->processChangedEntity($processedDTO);
            $this->postProcess($args, $isFlushNeeded);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args): void
    {
        $processedDTO = $this->prepareDTO($args);
        if ($processedDTO && !empty($this->getWysiwygFields($processedDTO->getProcessedEntity()))) {
            $isFlushNeeded = $this->processor->onPreRemove($processedDTO);
            $this->postProcess($args, $isFlushNeeded);
        }
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
     * @return WYSIWYGProcessedDTO|null
     */
    private function prepareDTO(LifecycleEventArgs $args): ?WYSIWYGProcessedDTO
    {
        if (!$this->enabled || $args->getEntity() instanceof LocalizedFallbackValue) {
            return null;
        }

        return new WYSIWYGProcessedDTO(
            WYSIWYGProcessedEntityDTO::createFromLifecycleEventArgs($args)
        );
    }

    /**
     * @param WYSIWYGProcessedDTO $processedDTO
     * @return bool
     */
    private function processChangedEntity(WYSIWYGProcessedDTO $processedDTO): bool
    {
        $processedEntity = $processedDTO->getProcessedEntity();
        if (\count($processedEntity->getMetadata()->getIdentifier()) !== 1) {
            // Composite keys are not supported.
            return false;
        }

        $isFlushNeeded = $this->processEntityRelations($processedDTO);

        $wysiwygFields = $this->getWysiwygFields($processedEntity);
        if (!$wysiwygFields) {
            return $isFlushNeeded;
        }

        $acceptedTwigFunctions = $this->processor->getAcceptedTwigFunctions();
        if (!$acceptedTwigFunctions) {
            return $isFlushNeeded;
        }

        $changedFields = $processedEntity->filterChangedFields(\array_keys($wysiwygFields));
        if (!$changedFields) {
            return $isFlushNeeded;
        }

        foreach ($changedFields as $fieldName => $fieldValue) {
            $foundTwigFunctionCalls = $this->twigParser->findFunctionCalls($fieldValue, $acceptedTwigFunctions);

            $processedEntityFieldDTO = $processedEntity->withField($fieldName, $wysiwygFields[$fieldName]);

            $ownerEntityDTO = $processedDTO->isSelfOwner()
                ? $processedDTO->getOwnerEntity()->withField($fieldName)
                : $processedDTO->getOwnerEntity();

            $processedFieldDTO = new WYSIWYGProcessedDTO($processedEntityFieldDTO, $ownerEntityDTO);

            $isFlushNeeded = $this->processor->processTwigFunctions($processedFieldDTO, $foundTwigFunctionCalls)
                || $isFlushNeeded;
        }

        return $isFlushNeeded;
    }

    /**
     * Process entity relations
     *
     * @param WYSIWYGProcessedDTO $processedDTO
     * @return bool
     */
    private function processEntityRelations(WYSIWYGProcessedDTO $processedDTO): bool
    {
        $processedEntity = $processedDTO->getProcessedEntity();
        $metadata = $processedDTO->getProcessedEntity()->getMetadata();

        $isFlushNeeded = false;
        foreach ($metadata->getAssociationMappings() as $associationName => $associationMapping) {
            if (!$this->isTargetLocalizedFallbackValue($associationMapping)) {
                continue;
            }

            $associationValue = $metadata->getFieldValue($processedEntity->getEntity(), $associationName);
            $ownerEntityDTO = $processedDTO->isSelfOwner()
                ? $processedDTO->getOwnerEntity()->withField($associationName)
                : $processedDTO->getOwnerEntity();

            // OneToMany
            if (\is_iterable($associationValue)) {
                foreach ($associationValue as $associatedEntity) {
                    $associatedEntityDTO = new WYSIWYGProcessedEntityDTO(
                        $processedEntity->getEntityManager(),
                        $associatedEntity
                    );

                    $associationProcessedDTO = new WYSIWYGProcessedDTO($associatedEntityDTO, $ownerEntityDTO);
                    $isFlushNeeded = $this->processChangedEntity($associationProcessedDTO) || $isFlushNeeded;
                }
                // ManyToOne, OneToOne
            } elseif (\is_object($associationValue)) {
                $associatedEntityDTO = new WYSIWYGProcessedEntityDTO(
                    $processedEntity->getEntityManager(),
                    $associationValue
                );

                $associationProcessedDTO = new WYSIWYGProcessedDTO($associatedEntityDTO, $ownerEntityDTO);
                $isFlushNeeded = $this->processChangedEntity($associationProcessedDTO) || $isFlushNeeded;
            }
        }

        return $isFlushNeeded;
    }

    /**
     * @param WYSIWYGProcessedEntityDTO $processedEntityDTO
     * @return array
     */
    private function getWysiwygFields(WYSIWYGProcessedEntityDTO $processedEntityDTO): array
    {
        $metadata = $processedEntityDTO->getMetadata();
        $entityName = $metadata->getName();
        if (!isset($this->fieldLists[$entityName])) {
            $this->fieldLists[$entityName] = [];
            $applicableFieldTypes = $this->processor->getApplicableFieldTypes();

            foreach ($metadata->getFieldNames() as $fieldName) {
                $mapping = $metadata->getFieldMapping($fieldName);
                if (\in_array($mapping['type'], $applicableFieldTypes, true)) {
                    $this->fieldLists[$entityName][$fieldName] = $mapping['type'];
                }
            }
        }

        return $this->fieldLists[$entityName];
    }

    /**
     * @param array $associationMapping
     * @return bool
     */
    private function isTargetLocalizedFallbackValue(array $associationMapping): bool
    {
        return isset($associationMapping['targetEntity'])
            && $associationMapping['targetEntity'] === LocalizedFallbackValue::class;
    }
}
