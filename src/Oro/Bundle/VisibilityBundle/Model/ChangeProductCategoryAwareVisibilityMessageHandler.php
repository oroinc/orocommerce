<?php

namespace Oro\Bundle\VisibilityBundle\Model;

/**
 * Decorator of VisibilityMessageHandler which additionally aggregates
 * 'oro_visibility.visibility.change_product_category' messages into one.
 */
class ChangeProductCategoryAwareVisibilityMessageHandler extends VisibilityMessageHandler
{
    private const TOPIC = 'oro_visibility.visibility.change_product_category';

    /**
     * {@inheritdoc}
     *
     * Aggregates 'oro_visibility.visibility.change_product_category' messages into one.
     */
    public function sendScheduledMessages(): void
    {
        foreach ($this->scheduledMessages as $topic => $entities) {
            if ($entities) {
                if ($topic === static::TOPIC) {
                    $productIds = [];
                    foreach ($entities as $entity) {
                        $message = $this->messageFactory->createMessage($entity);
                        $productIds[] = $message[MessageFactoryInterface::ID];
                    }
                    $this->messageProducer->send(
                        $topic,
                        [
                            MessageFactoryInterface::ID => count($productIds) > 1 ? $productIds : reset($productIds),
                            MessageFactoryInterface::ENTITY_CLASS_NAME =>
                                $message[MessageFactoryInterface::ENTITY_CLASS_NAME],
                        ]
                    );
                } else {
                    foreach ($entities as $entity) {
                        $message = $this->messageFactory->createMessage($entity);
                        $this->messageProducer->send($topic, $message);
                    }
                }
            }
        }

        $this->scheduledMessages = [];
    }
}
