<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;

/**
 * Interface for website search listeners for Product.
 */
interface WebsiteSearchProductIndexerListenerInterface
{
    public function onWebsiteSearchIndex(IndexEntityEvent $event);
}
