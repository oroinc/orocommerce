<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\EventListener;

use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\WebsiteSearchMappingEvent;

/**
 * Tracks WebsiteSearchMappingEvent events.
 */
class WebsiteSearchMappingListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    /**
     * @var array
     */
    private $triggeredEvents = [];

    public function __construct()
    {
        $this->enabled = false;
    }

    /**
     * @param WebsiteSearchMappingEvent $event
     */
    public function onWebsiteSearchMapping(WebsiteSearchMappingEvent $event): void
    {
        $this->triggeredEvents[] = $event;
    }

    /**
     * @return array
     */
    public function getTriggeredEvents(): array
    {
        return $this->triggeredEvents;
    }

    public function clearTriggeredEvents(): void
    {
        $this->triggeredEvents = [];
    }
}
