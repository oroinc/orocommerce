<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event for website search entity indexation.
 */
final class ReindexationRequestEvent extends Event
{
    const EVENT_NAME = 'oro_website_search.reindexation_request';
    /**
     * @var array
     */
    private $classesNames;

    /**
     * @var array
     */
    private $websitesIds;

    /**
     * @var array
     */
    private $ids;

    /**
     * @var boolean
     */
    private $scheduled;

    /**
     * @var null|array
     */
    private $fieldGroups;

    public function __construct(
        array $classesNames = [],
        array $websitesIds = [],
        array $ids = [],
        bool $scheduled = true,
        array $fieldGroups = null
    ) {
        $this->classesNames = $classesNames;
        $this->websitesIds  = $websitesIds;
        $this->ids          = $ids;
        $this->scheduled    = $scheduled;
        $this->fieldGroups  = $fieldGroups;
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

    /**
     * @return boolean
     */
    public function isScheduled(): bool
    {
        return $this->scheduled;
    }

    public function getFieldGroups(): ?array
    {
        return $this->fieldGroups;
    }
}
