<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;

class ProductImageDemoDataFixturesListener
{
    const LISTENERS = [
        'oro_product.event_listener.product_image_resize_listener',
    ];

    /** @var OptionalListenerManager */
    protected $listenerManager;

    /**
     * @param OptionalListenerManager $listenerManager
     */
    public function __construct(OptionalListenerManager $listenerManager)
    {
        $this->listenerManager = $listenerManager;
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    public function onPreLoad(MigrationDataFixturesEvent $event)
    {
        if (!$event->isDemoFixtures()) {
            return;
        }

        $this->listenerManager->disableListeners(self::LISTENERS);
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    public function onPostLoad(MigrationDataFixturesEvent $event)
    {
        if (!$event->isDemoFixtures()) {
            return;
        }

        $this->listenerManager->enableListeners(self::LISTENERS);
    }
}
