<?php

namespace OroB2B\Bundle\ShippingBundle\Factory;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ShippingBundle\Model\ShippingOrigin;

class ShippingOriginModelFactory
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param array $values
     *
     * @return ShippingOrigin
     */
    public function create(array $values)
    {
        $entity = new ShippingOrigin($values);

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
