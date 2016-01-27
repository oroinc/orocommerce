<?php

namespace OroB2B\Bundle\TaxBundle\Factory;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\TaxBundle\Model\TaxBaseExclusion;

class TaxBaseExclusionFactory
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param array $value
     * @return TaxBaseExclusion
     */
    public function create($value)
    {
        $entity = new TaxBaseExclusion();

        if (!empty($value['country'])) {
            /** @var Country $country */
            $country = $this->doctrineHelper->getEntityReference('OroAddressBundle:Country', $value['country']);
            $entity->setCountry($country);
        }

        if (!empty($value['region'])) {
            /** @var Region $region */
            $region = $this->doctrineHelper->getEntityReference('OroAddressBundle:Region', $value['region']);
            $entity->setRegion($region);
        }

        if (!empty($value['option'])) {
            $entity->setOption($value['option']);
        }

        return $entity;
    }
}
