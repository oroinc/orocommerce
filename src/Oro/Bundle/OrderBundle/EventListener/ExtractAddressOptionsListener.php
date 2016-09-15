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
        $addressModel  = $event->getModel();

        $addressModel
            ->setFirstName((string)$entity->getFirstName())
            ->setLastName((string)$entity->getLastName())
            ->setStreet((string)$entity->getStreet())
            ->setStreet2((string)$entity->getStreet2())
            ->setCity((string)$entity->getCity())
            ->setCountryIso2((string)$entity->getCountryIso2())
            ->setRegionCode((string)$entity->getRegionCode())
            ->setPostalCode((string)$entity->getPostalCode());

        $event->setModel($addressModel);
    }
}
