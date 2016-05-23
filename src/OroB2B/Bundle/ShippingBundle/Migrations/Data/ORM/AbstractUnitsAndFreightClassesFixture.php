<?php

namespace OroB2B\Bundle\ShippingBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\ShippingBundle\Entity\LengthUnit;
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
     * @param array $lengthUnits
     */
    protected function addLengthUnits(ObjectManager $manager, array $lengthUnits)
    {
        $repository = $manager->getRepository('OroB2BShippingBundle:LengthUnit');
        foreach ($lengthUnits as $unit) {
            if (!$repository->findOneBy(['code' => $unit['code']])) {
                $entity = new LengthUnit();
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
