<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationTriggerEvent;
use Oro\Bundle\SearchBundle\EventListener\IndexationListenerTrait;

class IndexationTriggeringListener implements OptionalListenerInterface
{
    use IndexationListenerTrait;
    
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
     * Add an entity to a helper array before sending them with an ReindexationTriggerEvent
     *
     * @param $entity
     * @throws \InvalidArgumentException
     */
    protected function scheduleForSendingWithEvent($entity)
    {
        if (!(is_object($entity) && method_exists($entity, 'getId'))) {
            throw new \InvalidArgumentException('Entity must be an object with `getId` method.');
        }

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

            /**
             * @var object $entity
             */
            foreach ($entities as $entity) {
                $ids[] = $entity->getId();
            }

            $websiteId = $this->websiteManager->getCurrentWebsite() !== null ?
                $this->websiteManager->getCurrentWebsite()->getId() : null;

            $reindexationTriggerEvent = new ReindexationTriggerEvent(
                $class,
                $websiteId,
                $ids
            );

            $this->dispatcher->dispatch(ReindexationTriggerEvent::EVENT_NAME, $reindexationTriggerEvent);
        }

        $this->changedEntities = [];
    }
}
