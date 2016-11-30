<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

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
    private $scheduled = true;

    /**
     * @param array $classesNames
     * @param array $websitesIds
     * @param array $ids
     * @param bool  $scheduled
     */
    public function __construct(
        array $classesNames = [],
        array $websitesIds = [],
        array $ids = [],
        $scheduled = true
    ) {
        $this->classesNames = $classesNames;
        $this->websitesIds  = $websitesIds;
        $this->ids          = $ids;
        $this->scheduled    = $scheduled;
    }

    /**
     * @return array
     */
    public function getClassesNames()
    {
        return $this->classesNames;
    }

    /**
     * @return array
     */
    public function getWebsitesIds()
    {
        return $this->websitesIds;
    }

    /**
     * @return array
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
