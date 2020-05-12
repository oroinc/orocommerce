<?php

namespace Oro\Bundle\VisibilityBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\VisibilityBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Sends MQ message to change category position when a parent category for a category is changed.
 * Sends MQ message to remove category when a category is removed.
 */
class CategoryListener
{
    /** @var MessageProducerInterface */
    private $messageProducer;

    /**
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(MessageProducerInterface $messageProducer)
    {
        $this->messageProducer = $messageProducer;
    }

    /**
     * @param Category           $category
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(Category $category, PreUpdateEventArgs $event): void
    {
        if ($event->hasChangedField(Category::FIELD_PARENT_CATEGORY)) {
            $this->messageProducer->send(Topics::CATEGORY_POSITION_CHANGE, ['id' => $category->getId()]);
        }
    }

    /**
     * @param Category $category
     */
    public function preRemove(Category $category): void
    {
        $this->messageProducer->send(Topics::CATEGORY_REMOVE, ['id' => $category->getId()]);
    }
}
