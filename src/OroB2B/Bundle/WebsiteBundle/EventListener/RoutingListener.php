<?php

namespace Oro\Bundle\WebsiteBundle\EventListener;

use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RoutingListener
{
    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    /**
     * @param WebsiteManager $websiteManager
     */
    public function __construct(WebsiteManager $websiteManager)
    {
        $this->websiteManager = $websiteManager;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $request->attributes->set('current_website', $this->websiteManager->getCurrentWebsite());
    }
}
