<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\InventoryBundle\Provider\ProductUpcomingProvider;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;

/** This event listener normalizes and denormalizes 'isUpcoming' field data during product import/export */
class ProductNormalizerEventListener
{
    /**
     * @var ProductUpcomingProvider
     */
    protected $productUpcomingProvider;

    /** @param ProductUpcomingProvider $productUpcomingProvider */
    public function __construct(ProductUpcomingProvider $productUpcomingProvider)
    {
        $this->productUpcomingProvider = $productUpcomingProvider;
    }

    /** @param ProductNormalizerEvent $event */
    public function normalize(ProductNormalizerEvent $event)
    {
        $object = $event->getProduct();
        if ($this->productUpcomingProvider->isUpcoming($object)) {
            $data = $event->getPlainData();
            $data[ProductUpcomingProvider::IS_UPCOMING] = '1';
            $date = $this->productUpcomingProvider->getAvailabilityDate($object);
            if ($date) {
                $data['availability_date'] = $date->format('Y-m-d\TH:i:sO');
            }
            $event->setPlainData($data);
        }
    }
}
