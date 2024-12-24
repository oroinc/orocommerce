<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event for website search entity indexation.
 */
final class ReindexationRequestEvent extends Event
{
    public const string EVENT_NAME = 'oro_website_search.reindexation_request';

    public function __construct(
        private array $classesNames = [],
        private array $websitesIds = [],
        private array $ids = [],
        private bool $scheduled = true,
        private ?array $fieldGroups = null,
        private ?int $batchSize = null
    ) {
    }

    public function getClassesNames(): array
    {
        return $this->classesNames;
    }

    public function getWebsitesIds(): array
    {
        return $this->websitesIds;
    }

    public function getIds(): array
    {
        return $this->ids;
    }

    public function isScheduled(): bool
    {
        return $this->scheduled;
    }

    public function getFieldGroups(): ?array
    {
        return $this->fieldGroups;
    }

    public function getBatchSize(): ?int
    {
        return $this->batchSize;
    }
}
