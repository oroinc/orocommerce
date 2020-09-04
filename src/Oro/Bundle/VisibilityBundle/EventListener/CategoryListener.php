<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Oro\Bundle\CatalogBundle\Event\ProductsChangeRelationEvent;
use Oro\Bundle\VisibilityBundle\Model\CategoryMessageHandler;
use Oro\Bundle\VisibilityBundle\Model\ProductMessageHandler;
use Oro\Bundle\VisibilityBundle\Model\VisibilityMessageHandler;

/**
 * Listens product category changes and sends MQ messages to recalculate products visibility.
 */
class CategoryListener
{
    /**
     * @var CategoryMessageHandler
     */
    protected $categoryMessageHandler;

    /**
     * @var ProductMessageHandler
     */
    protected $productMessageHandler;

    /**
     * @var VisibilityMessageHandler
     */
    protected $visibilityMessageHandler;

    /**
     * @var string
     */
    protected $topic = '';

    /**
     * @param ProductMessageHandler $productMessageHandler
     */
    public function __construct(ProductMessageHandler $productMessageHandler)
    {
        $this->productMessageHandler = $productMessageHandler;
    }

    /**
     * @param VisibilityMessageHandler|null $visibilityMessageHandler
     */
    public function setVisibilityMessageHandler(?VisibilityMessageHandler $visibilityMessageHandler): void
    {
        $this->visibilityMessageHandler = $visibilityMessageHandler;
    }

    /**
     * @param $topic
     */
    public function setTopic($topic)
    {
        $this->topic = (string)$topic;
    }

    /**
     * @param ProductsChangeRelationEvent $event
     */
    public function onProductsChangeRelation(ProductsChangeRelationEvent $event)
    {
        $products = $event->getProducts();
        foreach ($products as $product) {
            // Message should be send only for already existing products
            // New products has own queue message for visibility calculation
            if (!$product->getId()) {
                continue;
            }

            if ($this->visibilityMessageHandler) {
                $this->visibilityMessageHandler->addMessageToSchedule($this->topic, $product);
            } else {
                $this->productMessageHandler->addProductMessageToSchedule($this->topic, $product);
            }
        }
    }
}
