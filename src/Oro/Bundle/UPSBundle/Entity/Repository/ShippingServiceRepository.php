<?php

namespace Oro\Bundle\UPSBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\UPSBundle\Entity\ShippingService;

class ShippingServiceRepository extends EntityRepository
{
    /**
     * @param Country $country
     * @return ShippingService[]
     */
    public function getShippingServicesByCountry(Country $country)
    {
        return $this
            ->createQueryBuilder('shippingService')
            ->andWhere('shippingService.country = :country')
            ->orderBy('shippingService.description')
            ->setParameter(':country', $country->getIso2Code())
            ->getQuery()
            ->getResult();
    }
}
