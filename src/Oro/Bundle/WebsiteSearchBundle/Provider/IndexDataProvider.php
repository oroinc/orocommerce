<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\CollectContextEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class corresponds for triggering all events during indexation
 * and returning all collected event data
 */
class IndexDataProvider
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityAliasResolver $entityAliasResolver
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EntityAliasResolver $entityAliasResolver
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->entityAliasResolver = $entityAliasResolver;
    }

    /**
     * @param int $websiteId
     * @param array $context
     * @return array
     */
    public function collectContextForWebsite($websiteId, array $context)
    {
        $context[AbstractIndexer::CONTEXT_WEBSITE_ID_KEY] = $websiteId;
        $collectContextEvent = new CollectContextEvent($context);
        $this->eventDispatcher->dispatch(CollectContextEvent::NAME, $collectContextEvent);

        return $collectContextEvent->getContext();
    }

    /**
     * @param string $entityClass
     * @param object[] $restrictedEntities
     * @param $context
     * @return array
     */
    public function getEntitiesData($entityClass, $restrictedEntities, $context)
    {
        $indexEntityEvent = new IndexEntityEvent($entityClass, $restrictedEntities, $context);
        $this->eventDispatcher->dispatch(IndexEntityEvent::NAME, $indexEntityEvent);

        return $indexEntityEvent->getEntitiesData();
    }

    /**
     * @param $entityClass
     * @param $queryBuilder
     * @param $context
     * @return QueryBuilder
     */
    public function getRestrictedEntitiesQueryBuilder($entityClass, $queryBuilder, $context)
    {
        $entityAlias = $this->entityAliasResolver->getAlias($entityClass);

        $restrictEntitiesEvent = new RestrictIndexEntityEvent($queryBuilder, $context);
        $this->eventDispatcher->dispatch(RestrictIndexEntityEvent::NAME, $restrictEntitiesEvent);
        $this->eventDispatcher->dispatch(
            sprintf('%s.%s', RestrictIndexEntityEvent::NAME, $entityAlias),
            $restrictEntitiesEvent
        );

        return $restrictEntitiesEvent->getQueryBuilder();
    }
}
