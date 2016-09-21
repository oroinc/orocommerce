<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

final class ReindexationTriggerEvent extends Event
{
    const EVENT_NAME = 'oro_website_search.reindexation_trigger';

    /**
     * @var string
     */
    private $className;

    /**
     * @var int
     */
    private $websiteId;

    /**
     * @var int[]|null
     */
    private $ids;

    /**
     * @var boolean
     */
    private $scheduled = true;

    /**
     * @param string $className
     * @param int $websiteId
     * @param \int[]|null $ids
     * @param \boolean $scheduled
     */
    public function __construct(
        $className = null,
        $websiteId = null,
        array $ids = null,
        $scheduled = true
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
     * @return \int[]|null
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
