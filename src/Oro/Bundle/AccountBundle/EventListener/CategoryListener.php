<?php

namespace Oro\Bundle\AccountBundle\EventListener;

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
            $this->productMessageHandler->addProductMessageToSchedule(
                $this->topic,
                $product
            );
        }
    }
}
