<?php

namespace OroB2B\Bundle\ShippingBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\ShippingBundle\Entity\DimensionUnit;
use OroB2B\Bundle\ShippingBundle\Entity\FreightClass;
use OroB2B\Bundle\ShippingBundle\Entity\WeightUnit;

abstract class AbstractUnitsAndFreightClassesFixture extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     * @param array $weightUnits
     */
    protected function addWeightUnits(ObjectManager $manager, array $weightUnits)
    {
        $repository = $manager->getRepository('OroB2BShippingBundle:WeightUnit');
        foreach ($weightUnits as $unit) {
            if (!$repository->findOneBy(['code' => $unit['code']])) {
                $entity = new WeightUnit();
                $entity->setCode($unit['code'])->setConversionRates($unit['conversion_rates']);

                $manager->persist($entity);
            }
        }
    }

    /**
     * @param ObjectManager $manager
     * @param array $dimensionUnits
     */
    protected function addDimensionUnits(ObjectManager $manager, array $dimensionUnits)
    {
        $repository = $manager->getRepository('OroB2BShippingBundle:DimensionUnit');
        foreach ($dimensionUnits as $unit) {
            if (!$repository->findOneBy(['code' => $unit['code']])) {
                $entity = new DimensionUnit();
                $entity->setCode($unit['code'])->setConversionRates($unit['conversion_rates']);

                $manager->persist($entity);
            }
        }
    }

    /**
     * @param ObjectManager $manager
     * @param array $freightClasses
     */
    protected function addFreightClasses(ObjectManager $manager, array $freightClasses)
    {
        $repository = $manager->getRepository('OroB2BShippingBundle:FreightClass');
        foreach ($freightClasses as $unit) {
            if (!$repository->findOneBy(['code' => $unit['code']])) {
                $entity = new FreightClass();
                $entity->setCode($unit['code']);

                $manager->persist($entity);
            }
        }
    }
}
