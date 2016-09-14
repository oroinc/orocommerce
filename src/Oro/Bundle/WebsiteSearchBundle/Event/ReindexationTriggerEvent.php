<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

final class ReindexationTriggerEvent extends Event
{
    const EVENT_NAME = 'oro_website_search.reindexation_triger';

    /**
     * @var string
     */
    private $className;

    /**
     * @var int
     */
    private $websiteId;

    /**
     * @var int[]
     */
    private $ids = [];

    /**
     * @var boolean
     */
    private $scheduled;

    /**
     * ReindexationTriggerEvent constructor.
     * @param string $className
     * @param int $websiteId
     * @param \int[] $ids
     * @param \boolean $scheduled
     */
    public function __construct(
        $className = null,
        $websiteId = null,
        array $ids = [],
        $scheduled = null
    ) {
        $this->className = $className;
        $this->websiteId = $websiteId;
        $this->ids = $ids;
        $this->scheduled = $scheduled;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return int
     */
    public function getWebsiteId()
    {
        return $this->websiteId;
    }

    /**
     * @return \int[]
     */
    public function getIds()
    {
        return $this->ids;
    }

    /**
     * @return boolean
     */
    public function isScheduled()
    {
        return $this->scheduled;
    }
}
