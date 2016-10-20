<?php

namespace Oro\Bundle\CustomerBundle\EventListener;

use Oro\Bundle\CatalogBundle\Event\ProductsChangeRelationEvent;
use Oro\Bundle\CatalogBundle\Model\CategoryMessageHandler;
use Oro\Bundle\ProductBundle\Model\ProductMessageHandler;

class CategoryListener
{
    /**
     * @var CategoryMessageHandler
     */
    protected $categoryMessageHandler;

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
            if ($product->getId()) {
                $this->productMessageHandler->addProductMessageToSchedule(
                    $this->topic,
                    $product
                );
            }
        }
    }
}
