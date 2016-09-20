<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationTriggerEvent;

class IndexationTriggeringListener implements OptionalListenerInterface
{
    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var array
     */
    protected $changedEntities = [];

    /**
     * @var SearchMappingProvider
     */
    protected $mappingProvider;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param SearchMappingProvider $mappingProvider
     * @param EventDispatcherInterface $dispatcher
     * @param WebsiteManager $websiteManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        SearchMappingProvider $mappingProvider,
        EventDispatcherInterface $dispatcher,
        WebsiteManager $websiteManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->mappingProvider = $mappingProvider;
        $this->dispatcher = $dispatcher;
        $this->websiteManager = $websiteManager;
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        $entityManager = $args->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        foreach (array_merge(
            $unitOfWork->getScheduledEntityInsertions(),
            $this->getEntitiesWithUpdatedIndexedFields($unitOfWork),
            $unitOfWork->getScheduledEntityDeletions()
        ) as $updatedEntity) {
            if (!$this->mappingProvider->isFieldsMappingExists(
                $this->doctrineHelper->getEntityClass($updatedEntity)
            )) {
                continue;
            }

            $this->scheduleForSendingWithEvent($updatedEntity);
        }
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        if (!empty($this->changedEntities)) {
            $this->triggerReindexationEvent();
        }
    }

    /**
     * @param UnitOfWork $uow
     *
     * @return object[]
     */
    protected function getEntitiesWithUpdatedIndexedFields(UnitOfWork $uow)
    {
        $entitiesToReindex = [];

        foreach ($uow->getScheduledEntityUpdates() as $hash => $entity) {
            $className = ClassUtils::getClass($entity);
            if (!$this->mappingProvider->isFieldsMappingExists($className)) {
                continue;
            }

            $entityConfig = $this->mappingProvider->getEntityConfig($className);

            $indexedFields = [];
            foreach ($entityConfig['fields'] as $fieldConfig) {
                $indexedFields[] = $fieldConfig['name'];
            }

            $changeSet = $uow->getEntityChangeSet($entity);
            $fieldsToReindex = array_intersect($indexedFields, array_keys($changeSet));

            if ($fieldsToReindex) {
                $entitiesToReindex[$hash] = $entity;
            }
        }

        return $entitiesToReindex;
    }

    /**
     * Add an entity to a helper array before sending them with and ReindexationTriggerEvent
     *
     * @param $entity
     */
    protected function scheduleForSendingWithEvent($entity)
    {
        $className = ClassUtils::getClass($entity);

        if (!isset($this->changedEntities[$className])) {
            $this->changedEntities[$className] = [];
        }

        $this->changedEntities[$className][] = $entity;
    }

    /**
     * Trigger the event and clear the scheduled data
     */
    protected function triggerReindexationEvent()
    {
        foreach ($this->changedEntities as $class => $entities) {
            $ids = [];

            foreach ($entities as $entity) {
                $ids[] = $entity->getId();
            }

            $reindexationTriggerEvent = new ReindexationTriggerEvent(
                $class,
                $this->websiteManager->getCurrentWebsite() !== null ?
                    $this->websiteManager->getCurrentWebsite()->getId() : null,
                $ids
            );

            $this->dispatcher->dispatch(ReindexationTriggerEvent::EVENT_NAME, $reindexationTriggerEvent);
        }

        $this->changedEntities = [];
    }
}
