<?php

namespace Oro\Bundle\VisibilityBundle\Entity\EntityListener;

use Oro\Bundle\VisibilityBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Sends MQ message to resolve category visibility when a category visibility related entity
 * is created, updated or removed.
 */
class CategoryVisibilityListener extends AbstractVisibilityListener
{
    public function __construct(MessageProducerInterface $messageProducer)
    {
        parent::__construct($messageProducer, Topics::CHANGE_CATEGORY_VISIBILITY);
    }
}
