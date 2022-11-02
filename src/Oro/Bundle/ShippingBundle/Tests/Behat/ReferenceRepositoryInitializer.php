<?php

namespace Oro\Bundle\ShippingBundle\Tests\Behat;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ShippingBundle\Entity\FreightClass;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    private const LENGTH_UNIT_MAPPING = ['m' => 'meter', 'cm' => 'centimeter'];

    /**
     * {@inheritdoc}
     */
    public function init(ManagerRegistry $doctrine, Collection $referenceRepository): void
    {
        $repository = $doctrine->getRepository(FreightClass::class);
        $referenceRepository->set('parcel', $repository->findOneBy(['code' => 'parcel']));

        /** @var WeightUnit $weightUnit */
        foreach ($doctrine->getRepository(WeightUnit::class)->findAll() as $weightUnit) {
            $referenceRepository->set($weightUnit->getCode(), $weightUnit);
        }

        /** @var LengthUnit $lengthUnit */
        foreach ($doctrine->getRepository(LengthUnit::class)->findAll() as $lengthUnit) {
            $code = $lengthUnit->getCode();
            $referenceRepository->set(self::LENGTH_UNIT_MAPPING[$code] ?? $code, $lengthUnit);
        }
    }
}
