<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class ProductImageResizeListener
{
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
        $this->producer->send(self::IMAGE_RESIZE_TOPIC, $event->getData());
    }
}
