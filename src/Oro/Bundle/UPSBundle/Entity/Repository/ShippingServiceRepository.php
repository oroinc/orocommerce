<?php

namespace Oro\Bundle\UPSBundle\Entity\Repository;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\AddressBundle\Entity\Country;

class ShippingServiceRepository extends EntityRepository
{
    /**
     * @param Country $country
     * @return Collection
     */
    public function getShippingServicesByCountry(Country $country)
    {
        $result = $this
            ->createQueryBuilder('shippingService')
            ->andWhere('shippingService.country = :country')
            ->orderBy('shippingService.description')
            ->setParameter(':country', $country->getIso2Code())
            ->getQuery()
            ->getResult();
        return $result;
    }
}
