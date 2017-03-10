<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\SEOBundle\Event\UrlItemsProviderEndEvent;
use Oro\Bundle\SEOBundle\Limiter\WebCatalogProductLimiter;

class ProductUrlItemsProviderEndListener
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
     * @param UrlItemsProviderEndEvent $event
     */
    public function onEnd(UrlItemsProviderEndEvent $event)
    {
        $this->webCatalogProductLimiter->erase($event->getVersion());
    }
}
