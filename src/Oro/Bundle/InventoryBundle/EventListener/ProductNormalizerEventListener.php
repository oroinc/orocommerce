<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;

/** This event listener normalizes and denormalizes 'availabilityDate' field data during product import/export */
class ProductNormalizerEventListener
{
    /**
     * @var UpcomingProductProvider
     */
    protected $productUpcomingProvider;

    /** @param UpcomingProductProvider $productUpcomingProvider */
    public function __construct(UpcomingProductProvider $productUpcomingProvider)
    {
        $this->productUpcomingProvider = $productUpcomingProvider;
    }

    /** @param ProductNormalizerEvent $event */
    public function normalize(ProductNormalizerEvent $event)
    {
        if (!$this->isApplicable($event->getContext())) {
            return;
        }

        $object = $event->getProduct();
        if ($this->productUpcomingProvider->isUpcoming($object)) {
            $data = $event->getPlainData();
            $date = $this->productUpcomingProvider->getAvailabilityDate($object);
            if ($date) {
                $data['availability_date'] = $date->format('Y-m-d\TH:i:sO');
            }
            $event->setPlainData($data);
        }
    }

    /**
     * No need to normalize related products
     *
     * @param array $context
     * @return bool
     */
    protected function isApplicable(array $context)
    {
        return !array_key_exists('fieldName', $context);
    }
}
