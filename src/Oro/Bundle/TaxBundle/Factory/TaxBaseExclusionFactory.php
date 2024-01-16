<?php

namespace Oro\Bundle\TaxBundle\Factory;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Model\TaxBaseExclusion;

/**
 * Creates TaxBaseExclusion model from array
 */
class TaxBaseExclusionFactory
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param array $values
     * @return TaxBaseExclusion
     */
    public function create($values)
    {
        $entity = new TaxBaseExclusion();

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

        if (!empty($values['option'])) {
            $entity->setOption($values['option']);
        }

        return $entity;
    }
}
