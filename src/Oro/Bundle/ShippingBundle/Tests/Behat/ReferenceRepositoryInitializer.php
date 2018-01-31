<?php

namespace Oro\Bundle\ShippingBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Oro\Bundle\ShippingBundle\Entity\FreightClass;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    const PARCEL_CODE = 'parcel';
    const KILOGRAM_CODE = 'kg';
    const METER_CODE = 'm';

    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, AliceCollection $referenceRepository)
    {
        /** @var EntityRepository $repository */
        $repository = $doctrine->getManager()->getRepository(FreightClass::class);
        $referenceRepository->set('parcel', $repository->findOneBy(['code' => self::PARCEL_CODE]));

        /** @var EntityRepository $repository */
        $repository = $doctrine->getManager()->getRepository(WeightUnit::class);
        $referenceRepository->set('kg', $repository->findOneBy(['code' => self::KILOGRAM_CODE]));

        /** @var EntityRepository $repository */
        $repository = $doctrine->getManager()->getRepository(LengthUnit::class);
        $referenceRepository->set('meter', $repository->findOneBy(['code' => self::METER_CODE]));
    }
}
