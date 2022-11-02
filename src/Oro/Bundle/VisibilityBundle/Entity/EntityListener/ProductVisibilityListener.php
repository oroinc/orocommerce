<?php

namespace Oro\Bundle\VisibilityBundle\Entity\EntityListener;

use Oro\Bundle\VisibilityBundle\Async\Topic\ResolveProductVisibilityTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Sends MQ message to resolve product visibility when a product visibility related entity
 * is created, updated or removed.
 */
class ProductVisibilityListener extends AbstractVisibilityListener
{
    public function __construct(MessageProducerInterface $messageProducer)
    {
        parent::__construct($messageProducer, ResolveProductVisibilityTopic::getName());
    }
}
