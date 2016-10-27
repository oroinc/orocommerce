<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\SearchBundle\EventListener\IndexationListenerTrait;

class IndexationRequestListener implements OptionalListenerInterface
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
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param WebsiteSearchMappingProvider $mappingProvider
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        WebsiteSearchMappingProvider $mappingProvider,
        EventDispatcherInterface $dispatcher
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->mappingProvider = $mappingProvider;
        $this->dispatcher = $dispatcher;
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
            if (!$this->mappingProvider->hasFieldsMapping(
                $this->doctrineHelper->getEntityClass($updatedEntity)
            )) {
                continue;
            }

            $this->scheduleForSendingWithEvent($updatedEntity);
        }
    }

    /**
     * @param AfterFormProcessEvent $event
     */
    public function beforeEntityFlush(AfterFormProcessEvent $event)
    {
        if (!$this->enabled) {
            return;
        }

        $updatedEntity = $event->getData();
        if (!$this->mappingProvider->hasFieldsMapping(
            $this->doctrineHelper->getEntityClass($updatedEntity)
        )) {
            return;
        }

        $this->scheduleForSendingWithEvent($updatedEntity);
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * Post flush listening method
     */
    public function postFlush()
    {
        if (!$this->enabled) {
            return;
        }

        if (!empty($this->changedEntities)) {
            $this->triggerReindexationEvent();
        }
    }

    /**
     * Add an entity to a helper array before sending them with an ReindexationRequestEvent
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

        if (!array_key_exists(spl_object_hash($entity), $this->changedEntities[$className])) {
            $this->changedEntities[$className][spl_object_hash($entity)] = $entity;
        }
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

            $reindexationRequestEvent = new ReindexationRequestEvent(
                [$class],
                [],
                $ids
            );

            $this->dispatcher->dispatch(ReindexationRequestEvent::EVENT_NAME, $reindexationRequestEvent);
        }

        $this->changedEntities = [];
    }
}
