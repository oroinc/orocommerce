<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Sends MQ message to resize product images.
 */
class ProductImageResizeListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    private MessageProducerInterface $producer;

    public function __construct(MessageProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    public function resizeProductImage(ProductImageResizeEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->producer->send($event->getTopicName(), $event->getData());
    }
}
