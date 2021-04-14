<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event that is triggered after the reindexation is finished for entities of the specified entity class.
 */
class AfterReindexEvent extends Event
{
    public const EVENT_NAME = 'oro_website_search.after_reindex';

    /** @var string */
    private string $entityClass;

    /** @var array */
    private array $websiteContext;

    /** @var array */
    private array $indexedEntityIds;

    /** @var array */
    private array $removedEntityIds;

    /**
     * @param string $entityClass
     * @param array $context
     * @param array $indexedEntityIds
     * @param array $removedEntityIds
     */
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

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * @return array
     */
    public function getWebsiteContext(): array
    {
        return $this->websiteContext;
    }

    /**
     * @return array
     */
    public function getIndexedEntityIds(): array
    {
        return $this->indexedEntityIds;
    }

    /**
     * @return array
     */
    public function getRemovedEntityIds(): array
    {
        return $this->removedEntityIds;
    }
}
