<?php

namespace Oro\Bundle\PaymentBundle\Provider;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\PaymentBundle\Model\AddressOptionModel;

/**
 * Gets address option model for address entity.
 */
class PaymentOrderShippingAddressOptionsProvider
{
    public function getShippingAddressOptions(AbstractAddress $entity): AddressOptionModel
    {
        return (new AddressOptionModel())
            ->setFirstName((string) $entity->getFirstName())
            ->setLastName((string) $entity->getLastName())
            ->setStreet((string) $entity->getStreet())
            ->setStreet2((string) $entity->getStreet2())
            ->setCity((string) $entity->getCity())
            ->setCountryIso2((string) $entity->getCountryIso2())
            ->setRegionCode((string) $entity->getRegionCode())
            ->setPostalCode((string) $entity->getPostalCode());
    }
}
