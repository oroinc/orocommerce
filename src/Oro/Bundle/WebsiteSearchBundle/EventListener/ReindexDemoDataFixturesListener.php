<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\EventListener\AbstractDemoDataFixturesListener;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Triggers full reindexation of website index after demo data are loaded.
 */
class ReindexDemoDataFixturesListener extends AbstractDemoDataFixturesListener
{
    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * @param OptionalListenerManager $listenerManager
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(OptionalListenerManager $listenerManager, EventDispatcherInterface $dispatcher)
    {
        parent::__construct($listenerManager);

        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function onPreLoad(MigrationDataFixturesEvent $event)
    {
        $this->beforeDisableListeners($event);
        $this->listenerManager->disableListeners($this->listeners);
        $this->afterDisableListeners($event);
    }

    /**
     * {@inheritDoc}
     */
    public function onPostLoad(MigrationDataFixturesEvent $event)
    {
        $this->beforeEnableListeners($event);
        $this->listenerManager->enableListeners($this->listeners);
        $this->afterEnableListeners($event);
    }

    /**
     * {@inheritDoc}
     */
    protected function afterEnableListeners(MigrationDataFixturesEvent $event)
    {
        $event->log('running full reindexation of website index');

        $this->dispatcher->dispatch(
            ReindexationRequestEvent::EVENT_NAME,
            new ReindexationRequestEvent([], [], [], false)
        );
    }
}
