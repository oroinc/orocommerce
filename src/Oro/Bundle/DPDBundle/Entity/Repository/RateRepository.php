<?php

namespace Oro\Bundle\DPDBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Entity\Rate;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;

class RateRepository extends EntityRepository
{
    /**
     * @param DPDTransport $transport
     * @param ShippingService $shippingService
     * @param AddressInterface $shippingAddress
     * @return \Doctrine\ORM\Query
     */
    public function findRatesByServiceAndDestinationQuery(
        DPDTransport $transport,
        ShippingService $shippingService,
        AddressInterface $shippingAddress
    ) {
        $qb = $this->createQueryBuilder('rate');
        $qb->select('rate')
            ->leftJoin('rate.country', 'country')
            ->leftJoin('rate.region', 'region')
            ->where('rate.transport = :transport')
            ->andWhere('rate.shippingService = :shippingService')
            ->andWhere($qb->expr()->orX(
                $qb->expr()->eq('country.iso2Code',':countryIso2Code'),
                $qb->expr()->isNull('country.iso2Code')
            ))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->eq('region.code',':regionCode'),
                $qb->expr()->isNull('region.code')
            ))
            ->orderBy('rate.country', 'DESC')
            ->addOrderBy('rate.region', 'DESC');
        $qb->setParameters([
            'transport' => $transport,
            'shippingService' => $shippingService,
            'countryIso2Code' =>  $shippingAddress->getCountryIso2(),
            'regionCode' => $shippingAddress->getRegionCode()
        ]);

        return $qb->getQuery();
    }

    /**
     * @param DPDTransport $transport
     * @param ShippingService $shippingService
     * @param AddressInterface $shippingAddress
     * @return Rate[]
     */
    public function findRatesByServiceAndDestination(
        DPDTransport $transport,
        ShippingService $shippingService,
        AddressInterface $shippingAddress
    ) {
        $results =
            $this->findRatesByServiceAndDestinationQuery($transport, $shippingService, $shippingAddress)
                ->getResult();

        return $results;
    }
}
