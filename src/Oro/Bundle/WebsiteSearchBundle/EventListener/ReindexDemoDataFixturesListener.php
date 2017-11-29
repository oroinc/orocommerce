<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

/**
 * Triggers full reindexation of website index after demo data are loaded.
 */
class ReindexDemoDataFixturesListener
{
    const LISTENERS = [
        'oro_website_search.reindex_request.listener',
    ];

    /** @var OptionalListenerManager */
    private $listenerManager;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /**
     * @param OptionalListenerManager $listenerManager
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        OptionalListenerManager $listenerManager,
        EventDispatcherInterface $dispatcher
    ) {
        $this->listenerManager = $listenerManager;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    public function onPreLoad(MigrationDataFixturesEvent $event)
    {
        $this->listenerManager->disableListeners(self::LISTENERS);
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    public function onPostLoad(MigrationDataFixturesEvent $event)
    {
        $this->listenerManager->enableListeners(self::LISTENERS);

        $event->log('running full reindexation of website index');
        $this->dispatcher->dispatch(
            ReindexationRequestEvent::EVENT_NAME,
            new ReindexationRequestEvent([], [], [], false)
        );
    }
}
