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

    /**
     * @param array $classesNames
     * @param array $websitesIds
     * @param array $ids
     * @param bool  $scheduled
     * @param null|array $fieldGroups
     */
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

    /**
     * @return array
     */
    public function getClassesNames(): array
    {
        return $this->classesNames;
    }

    /**
     * @return array
     */
    public function getWebsitesIds(): array
    {
        return $this->websitesIds;
    }

    /**
     * @return array
     */
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

    /**
     * @return array|null
     */
    public function getFieldGroups(): ?array
    {
        return $this->fieldGroups;
    }
}
