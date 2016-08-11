<?php

namespace OroB2B\Bundle\WebsiteBundle\EventListener;

use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;
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
