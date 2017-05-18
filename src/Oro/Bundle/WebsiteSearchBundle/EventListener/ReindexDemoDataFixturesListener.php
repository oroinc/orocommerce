<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

/**
 * Triggers full reindexation of website index after demo data are loaded.
 */
class ReindexDemoDataFixturesListener
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    public function onPostLoad(MigrationDataFixturesEvent $event)
    {
        if ($event->isDemoFixtures()) {
            $event->log('running full reindexation of website index');
            $this->dispatcher->dispatch(
                ReindexationRequestEvent::EVENT_NAME,
                new ReindexationRequestEvent([], [], [], false)
            );
        }
    }
}
