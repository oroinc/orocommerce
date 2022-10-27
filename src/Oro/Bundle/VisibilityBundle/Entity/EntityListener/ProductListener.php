<?php

namespace Oro\Bundle\VisibilityBundle\Entity\EntityListener;

use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Async\Topic\VisibilityOnChangeProductCategoryTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Sends MQ message to change product category when a product is created.
 */
class ProductListener implements OptionalListenerInterface
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

    public function postPersist(Product $product): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->messageProducer->send(VisibilityOnChangeProductCategoryTopic::getName(), ['id' => $product->getId()]);
    }
}
