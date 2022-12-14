<?php

namespace Oro\Bundle\ShippingBundle\Factory;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;

/**
 * The factory to create the ShippingOrigin object.
 */
class ShippingOriginModelFactory
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function create(array $values): ShippingOrigin
    {
        $entity = new ShippingOrigin($values);

        if (!empty($values['country'])) {
            /** @var Country $country */
            $country = $this->doctrineHelper->getEntityReference(Country::class, $values['country']);
            $entity->setCountry($country);
        }
        if (!empty($values['region'])) {
            /** @var Region $region */
            $region = $this->doctrineHelper->getEntityReference(Region::class, $values['region']);
            $entity->setRegion($region);
        }

        return $entity;
    }
}
