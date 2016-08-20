<?php

namespace Oro\Bundle\TaxBundle\Factory;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Model\Address;

class AddressModelFactory
{
    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
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
            $country = $this->doctrineHelper->getEntityReference('OroAddressBundle:Country', $values['country']);
            $entity->setCountry($country);
        }

        if (!empty($values['region'])) {
            /** @var Region $region */
            $region = $this->doctrineHelper->getEntityReference('OroAddressBundle:Region', $values['region']);
            $entity->setRegion($region);
        }

        return $entity;
    }
}
