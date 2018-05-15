<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class ProductImageResizeListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    const IMAGE_RESIZE_TOPIC = 'imageResize';

    /**
     * @var MessageProducerInterface
     */
    protected $producer;

    /**
     * @param MessageProducerInterface $producer
     */
    public function __construct(MessageProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    /**
     * @param ProductImageResizeEvent $event
     */
    public function resizeProductImage(ProductImageResizeEvent $event)
    {
        if (!$this->enabled) {
            return;
        }

        $this->producer->send(self::IMAGE_RESIZE_TOPIC, $event->getData());
    }
}
