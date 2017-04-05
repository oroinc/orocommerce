<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\SEOBundle\Event\UrlItemsProviderEvent;
use Oro\Bundle\SEOBundle\Limiter\WebCatalogProductLimiter;

class ProductUrlItemsProviderStartListener
{
    /**
     * @var WebCatalogProductLimiter
     */
    protected $webCatalogProductLimiter;

    /**
     * @param WebCatalogProductLimiter $webCatalogProductLimiter
     */
    public function __construct(WebCatalogProductLimiter $webCatalogProductLimiter)
    {
        $this->webCatalogProductLimiter = $webCatalogProductLimiter;
    }

    /**
     * @param UrlItemsProviderEvent $event
     */
    public function onStart(UrlItemsProviderEvent $event)
    {
        $this->webCatalogProductLimiter->prepareLimitation($event->getVersion(), $event->getWebsite());
    }
}
