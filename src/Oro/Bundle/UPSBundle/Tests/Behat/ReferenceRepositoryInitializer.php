<?php

namespace Oro\Bundle\UPSBundle\Tests\Behat;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;
use Oro\Bundle\UPSBundle\Entity\ShippingService;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    const UPS_2ND_DAY_AIR_DESCRIPTION = 'UPS 2nd Day Air';

    /**
     * {@inheritdoc}
     */
    public function init(ManagerRegistry $doctrine, Collection $referenceRepository): void
    {
        $repository = $doctrine->getManager()->getRepository(ShippingService::class);
        /** @var ShippingService $classicDpdShippingService */
        $ups2ndDayAirShippingService = $repository->findOneBy(['description' => self::UPS_2ND_DAY_AIR_DESCRIPTION]);
        $referenceRepository->set('UPS2ndDayAirShippingService', $ups2ndDayAirShippingService);
    }
}
