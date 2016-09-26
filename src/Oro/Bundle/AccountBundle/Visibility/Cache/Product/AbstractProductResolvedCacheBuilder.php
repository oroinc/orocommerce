<?php

namespace Oro\Bundle\AccountBundle\Visibility\Cache\Product;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationTriggerEvent;

abstract class AbstractProductResolvedCacheBuilder extends AbstractResolvedCacheBuilder
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param Product $product
     * @param Website $website
     */
    protected function triggerProductReindexation(Product $product, Website $website)
    {
        $event = new ReindexationTriggerEvent(
            ClassUtils::getClass($product),
            $website->getId(),
            [$product->getId()],
            false
        );
        $this->eventDispatcher->dispatch(ReindexationTriggerEvent::EVENT_NAME, $event);
    }
}
