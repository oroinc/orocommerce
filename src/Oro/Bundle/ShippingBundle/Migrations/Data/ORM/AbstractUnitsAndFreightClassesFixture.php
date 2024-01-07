<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ShippingBundle\Entity\FreightClass;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;

/**
 * Abstract class for loading weight units and freight classes
 */
abstract class AbstractUnitsAndFreightClassesFixture extends AbstractFixture
{
    protected function addUpdateWeightUnits(ObjectManager $manager, array $weightUnits)
    {
        $repository = $manager->getRepository(WeightUnit::class);
        foreach ($weightUnits as $unit) {
            $entity = $repository->findOneBy(['code' => $unit['code']]);
            if (!$entity) {
                $entity = new WeightUnit();
            }

            $entity->setCode($unit['code'])->setConversionRates($unit['conversion_rates']);
            $manager->persist($entity);
        }
    }

    protected function addUpdateLengthUnits(ObjectManager $manager, array $lengthUnits)
    {
        $repository = $manager->getRepository(LengthUnit::class);
        foreach ($lengthUnits as $unit) {
            $entity = $repository->findOneBy(['code' => $unit['code']]);
            if (!$entity) {
                $entity = new LengthUnit();
            }

            $entity->setCode($unit['code'])->setConversionRates($unit['conversion_rates']);
            $manager->persist($entity);
        }
    }

    protected function addUpdateFreightClasses(ObjectManager $manager, array $freightClasses)
    {
        $repository = $manager->getRepository(FreightClass::class);
        foreach ($freightClasses as $unit) {
            $entity = $repository->findOneBy(['code' => $unit['code']]);
            if (!$entity) {
                $entity = new FreightClass();
            }

            $entity->setCode($unit['code']);
            $manager->persist($entity);
        }
    }
}
