<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\PaymentBundle\Event\ExtractShippingAddressOptionsEvent;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;

class ExtractShippingAddressOptionsListener
{
    /**
     * @param ExtractShippingAddressOptionsEvent $event
     */
    public function onExtractShippingAddressOptions(ExtractShippingAddressOptionsEvent $event)
    {
        /** @var OrderAddress $entity */
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
