<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\PaymentBundle\Event\ExtractAddressOptionsEvent;

class ExtractAddressOptionsListener
{
    /**
     * @param ExtractAddressOptionsEvent $event
     */
    public function onExtractShippingAddressOptions(ExtractAddressOptionsEvent $event)
    {
        /** @var AbstractAddress $entity */
        $entity = $event->getEntity();
        $options = [
            (string)$entity->getFirstName(),
            (string)$entity->getLastName(),
            (string)$entity->getStreet(),
            (string)$entity->getStreet2(),
            (string)$entity->getCity(),
            (string)$entity->getRegionCode(),
            (string)$entity->getPostalCode(),
            (string)$entity->getCountryIso2(),
        ];

        $event->setOptions($event->applyKeys($options));
    }
}
