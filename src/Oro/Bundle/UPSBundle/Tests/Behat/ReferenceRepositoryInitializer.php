<?php

namespace Oro\Bundle\UPSBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;
use Oro\Bundle\UPSBundle\Entity\ShippingService as UPSShippingService;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    const UPS_2ND_DAY_AIR_DESCRIPTION = 'UPS 2nd Day Air';

    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, Collection $referenceRepository)
    {
        /** @var EntityRepository $repository */
        $repository = $doctrine->getManager()->getRepository('OroUPSBundle:ShippingService');
        /** @var UPSShippingService $classicDpdShippingService */
        $ups2ndDayAirShippingService = $repository->findOneBy(['description' => self::UPS_2ND_DAY_AIR_DESCRIPTION]);
        $referenceRepository->set('UPS2ndDayAirShippingService', $ups2ndDayAirShippingService);
    }
}
