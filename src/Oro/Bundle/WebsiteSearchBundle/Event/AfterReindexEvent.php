<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event that is triggered after the reindexation is finished for entities of the specified entity class.
 */
class AfterReindexEvent extends Event
{
    public const EVENT_NAME = 'oro_website_search.after_reindex';

    private string $entityClass;

    private array $websiteContext;

    private array $indexedEntityIds;

    private array $removedEntityIds;

    public function __construct(
        string $entityClass,
        array $context = [],
        array $indexedEntityIds = [],
        array $removedEntityIds = []
    ) {
        $this->entityClass = $entityClass;
        $this->websiteContext = $context;
        $this->indexedEntityIds = $indexedEntityIds;
        $this->removedEntityIds = $removedEntityIds;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getWebsiteContext(): array
    {
        return $this->websiteContext;
    }

    public function getIndexedEntityIds(): array
    {
        return $this->indexedEntityIds;
    }

    public function getRemovedEntityIds(): array
    {
        return $this->removedEntityIds;
    }
}
