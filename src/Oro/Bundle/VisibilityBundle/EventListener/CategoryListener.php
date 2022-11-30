<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Oro\Bundle\CatalogBundle\Event\ProductsChangeRelationEvent;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\VisibilityBundle\Async\Topic\VisibilityOnChangeProductCategoryTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Sends MQ message to change product category when an existing product relations is changed.
 */
class CategoryListener implements OptionalListenerInterface
{
    /** @var MessageProducerInterface */
    private $messageProducer;

    /** @var bool */
    private $enabled = true;

    public function __construct(MessageProducerInterface $messageProducer)
    {
        $this->messageProducer = $messageProducer;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }

    public function onProductsChangeRelation(ProductsChangeRelationEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $products = $event->getProducts();
        foreach ($products as $product) {
            // Message should be send only for already existing products
            // New products has own queue message for visibility calculation
            if ($product->getId()) {
                $this->messageProducer->send(
                    VisibilityOnChangeProductCategoryTopic::getName(),
                    ['id' => $product->getId()]
                );
            }
        }
    }
}
