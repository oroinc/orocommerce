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
     * @param ProductMessageHandler $productMessageHandler
     */
    public function __construct(ProductMessageHandler $productMessageHandler)
    {
        $this->productMessageHandler = $productMessageHandler;
    }

    /**
     * @param ProductsChangeRelationEvent $event
     */
    public function onProductsChangeRelation(ProductsChangeRelationEvent $event)
    {
        $products = $event->getProducts();
        foreach ($products as $product) {
            $this->productMessageHandler->addProductMessageToSchedule(
                'oro_account.visibility.change_product_category',
                $product
            );
        }
    }
}
