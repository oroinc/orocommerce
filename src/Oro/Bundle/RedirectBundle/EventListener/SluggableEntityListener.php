<?php

namespace Oro\Bundle\RedirectBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\Helper\DraftHelper;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\RedirectBundle\Async\Topic\GenerateDirectUrlForEntitiesTopic;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Schedule Slug regeneration for Sluggable entity which has changed Slug prototypes
 */
class SluggableEntityListener implements OptionalListenerInterface
{
    private const BATCH_SIZE = 100;

    use OptionalListenerTrait;

    /**
     * @var MessageFactoryInterface
     */
    private $messageFactory;

    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var array
     * [
     *      '<entityName>' => [<id1>, <id2>, ...],
     *       ...
     * ]
     */
    private $sluggableEntities = [];

    public function __construct(
        MessageFactoryInterface $messageFactory,
        MessageProducerInterface $messageProducer,
        ConfigManager $configManager
    ) {
        $this->messageFactory = $messageFactory;
        $this->messageProducer = $messageProducer;
        $this->configManager = $configManager;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        $entity = $args->getEntity();
        if ($entity instanceof SluggableInterface) {
            $this->scheduleEntitySlugCalculation($entity);
        }
    }

    public function onFlush(OnFlushEventArgs $event)
    {
        if (!$this->enabled) {
            return;
        }

        $unitOfWork = $event->getEntityManager()->getUnitOfWork();
        $sluggableEntitiesToCheck = [];
        foreach ($this->getUpdatedSlugs($unitOfWork) as $sluggableEntity) {
            $slugPrototypes = $sluggableEntity->getSlugPrototypes();
            if (!$slugPrototypes->count()) {
                continue;
            }

            if ($slugPrototypes instanceof PersistentCollection && $slugPrototypes->isDirty()) {
                // Slug prototypes collection is obviously changed, schedule slug recalculation.
                $this->scheduleEntitySlugCalculation($sluggableEntity);
                continue;
            }

            // Entity still might have changed slugPrototypes, though collection is not marked as dirty - in case when
            // an already existing slugPrototype gets updated.
            // Slug prototypes collections of such entities will be checked against each localizedFallbackValue
            // in ::checkSluggableEntities().
            $sluggableEntitiesToCheck[] = $sluggableEntity;
        }

        if ($sluggableEntitiesToCheck) {
            // Checks if there are changed slugPrototypes in the remaining sluggable entities.
            $this->checkSluggableEntities($unitOfWork, $sluggableEntitiesToCheck);
        }
    }

    /**
     * Goes through sluggable entities, checks if their slugPrototypes are changed and schedules for recalculation.
     */
    private function checkSluggableEntities(UnitOfWork $unitOfWork, array $sluggableEntitiesToCheck)
    {
        foreach ($this->getLocalizedValues($unitOfWork) as $localizedFallbackValue) {
            foreach ($sluggableEntitiesToCheck as $i => $sluggableEntity) {
                if ($sluggableEntity->hasSlugPrototype($localizedFallbackValue)) {
                    $this->scheduleEntitySlugCalculation($sluggableEntity);
                    unset($sluggableEntitiesToCheck[$i]);
                    break;
                }
            }
        }
    }

    public function postFlush()
    {
        foreach ($this->sluggableEntities as $entityClass => $entityInfo) {
            foreach ($entityInfo as $createRedirect => $ids) {
                while ($ids) {
                    $idsBatch = array_splice($ids, 0, self::BATCH_SIZE);
                    $message = $this->messageFactory->createMassMessage($entityClass, $idsBatch, (bool)$createRedirect);

                    $this->messageProducer->send(GenerateDirectUrlForEntitiesTopic::getName(), $message);
                }
            }
        }

        $this->sluggableEntities = [];
    }

    /**
     * @param UnitOfWork $unitOfWork
     * @return \Generator
     */
    protected function getLocalizedValues(UnitOfWork $unitOfWork)
    {
        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof LocalizedFallbackValue) {
                yield $entity;
            }
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof LocalizedFallbackValue) {
                yield $entity;
            }
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof LocalizedFallbackValue) {
                yield $entity;
            }
        }
    }

    /**
     * @param UnitOfWork $unitOfWork
     * @return \Generator
     */
    protected function getUpdatedSlugs(UnitOfWork $unitOfWork)
    {
        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof SluggableInterface) {
                yield $entity;
            }
        }
    }

    protected function scheduleEntitySlugCalculation(SluggableInterface $entity)
    {
        if ($entity instanceof DraftableInterface && DraftHelper::isDraft($entity)) {
            return;
        }

        if ($this->configManager->get('oro_redirect.enable_direct_url')) {
            $createRedirect = true;
            if ($entity->getSlugPrototypesWithRedirect()) {
                $createRedirect = $entity->getSlugPrototypesWithRedirect()->getCreateRedirect();
            }

            $entityClass = ClassUtils::getClass($entity);
            $this->sluggableEntities[$entityClass][$createRedirect][] = $entity->getId();
        }
    }
}
