<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class ProductDataStorageEventListener
{
    /**
     * @var ProductDataStorage
     */
    protected $productDataStorage;

    /**
     * ProductDataStorageEventListener constructor.
     * @param ProductDataStorage $productDataStorage
     */
    public function __construct(ProductDataStorage $productDataStorage)
    {
        $this->productDataStorage = $productDataStorage;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->getRequest()->get(ProductDataStorage::STORAGE_KEY)) {
            return;
        }

        if ($this->productDataStorage->isInvoked()) {
            return;
        }

        $this->productDataStorage->remove();
    }
}
