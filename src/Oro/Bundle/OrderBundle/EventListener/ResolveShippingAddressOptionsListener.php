<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\EntityBundle\Exception\IncorrectEntityException;
use Oro\Bundle\PaymentBundle\Event\ResolveShippingAddressOptionsEvent;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;

class ResolveShippingAddressOptionsListener
{
    /**
     * @param ResolveShippingAddressOptionsEvent $event
     * @throws IncorrectEntityException
     */
    public function onResolveShippingAddressOptions(ResolveShippingAddressOptionsEvent $event)
    {
        /** @var OrderAddress $entity */
        $entity = $event->getEntity();

        if (!$entity instanceof OrderAddress) {
            throw new IncorrectEntityException("OrderAddress Entity was expected");
        }

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

        $event->setOptions(array_combine($event->getKeys(), $options));
    }
}
