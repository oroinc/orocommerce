<?php

namespace Oro\Bundle\RedirectBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Schedule Slug regeneration for Sluggable entity which has changed Slug prototypes
 */
class SluggableEntityListener implements OptionalListenerInterface
{
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

    /**
     * @param MessageFactoryInterface $messageFactory
     * @param MessageProducerInterface $messageProducer
     * @param ConfigManager $configManager
     */
    public function __construct(
        MessageFactoryInterface $messageFactory,
        MessageProducerInterface $messageProducer,
        ConfigManager $configManager
    ) {
        $this->messageFactory = $messageFactory;
        $this->messageProducer = $messageProducer;
        $this->configManager = $configManager;
    }

    /**
     * @param LifecycleEventArgs $args
     */
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

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        if (!$this->enabled) {
            return;
        }

        $unitOfWork = $event->getEntityManager()->getUnitOfWork();
        foreach ($this->getChangedSluggableEntities($unitOfWork) as $changedSluggableEntity) {
            $this->scheduleEntitySlugCalculation($changedSluggableEntity);
        }
    }

    public function postFlush()
    {
        // Send MQ message to generate Slugs
        foreach ($this->sluggableEntities as $entityClass => $entityInfo) {
            foreach ($entityInfo as $createRedirect => $ids) {
                $message = $this->messageFactory->createMassMessage($entityClass, $ids, (bool)$createRedirect);

                $this->messageProducer->send(Topics::GENERATE_DIRECT_URL_FOR_ENTITIES, $message);
            }
        }

        $this->sluggableEntities = [];
    }

    /**
     * @param UnitOfWork $unitOfWork
     * @return array
     */
    protected function getChangedSluggableEntities(UnitOfWork $unitOfWork)
    {
        $result = [];
        foreach ($this->getUpdatedSlugs($unitOfWork) as $sluggableEntity) {
            foreach ($this->getLocalizedValues($unitOfWork) as $localizedFallbackValue) {
                if ($sluggableEntity->hasSlugPrototype($localizedFallbackValue)) {
                    $result[] = $sluggableEntity;
                    break;
                }
            }
        }

        return $result;
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

    /**
     * @param SluggableInterface $entity
     */
    protected function scheduleEntitySlugCalculation(SluggableInterface $entity)
    {
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
