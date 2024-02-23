<?php

namespace Oro\Bundle\TaxBundle\Factory;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Model\Address;

/**
 * Creates Address model from array
 */
class AddressModelFactory
{
    public function __construct(private DoctrineHelper $doctrineHelper)
    {
    }

    /**
     * @param array $values
     * @return Address
     */
    public function create($values)
    {
        $entity = new Address($values);

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
