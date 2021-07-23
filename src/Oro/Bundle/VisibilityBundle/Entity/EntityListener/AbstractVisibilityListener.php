<?php

namespace Oro\Bundle\VisibilityBundle\Entity\EntityListener;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * The base class to send MQ message to resolve entity visibility when its visibility related entity
 * is created, updated or removed.
 */
abstract class AbstractVisibilityListener implements OptionalListenerInterface
{
    /** @var MessageProducerInterface */
    private $messageProducer;

    /** @var string */
    private $topic;

    /** @var bool */
    private $enabled = true;

    protected function __construct(MessageProducerInterface $messageProducer, string $topic)
    {
        $this->messageProducer = $messageProducer;
        $this->topic = $topic;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }

    public function postPersist(VisibilityInterface $entity): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->messageProducer->send(
            $this->topic,
            [
                'entity_class_name' => ClassUtils::getClass($entity),
                'id'                => $entity->getId()
            ]
        );
    }

    public function preUpdate(VisibilityInterface $entity): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->messageProducer->send(
            $this->topic,
            [
                'entity_class_name' => ClassUtils::getClass($entity),
                'id'                => $entity->getId()
            ]
        );
    }

    public function preRemove(VisibilityInterface $entity): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->messageProducer->send(
            $this->topic,
            [
                'entity_class_name' => ClassUtils::getClass($entity),
                'target_class_name' => ClassUtils::getClass($entity->getTargetEntity()),
                'target_id'         => $entity->getTargetEntity()->getId(),
                'scope_id'          => $entity->getScope()->getId()
            ]
        );
    }
}
