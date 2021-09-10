<?php

namespace Oro\Bundle\UPSBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\UPSBundle\Entity\ShippingService;

/**
 * Doctrine repository for ShippingService entity
 */
class ShippingServiceRepository extends ServiceEntityRepository
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
