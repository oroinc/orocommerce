<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\ShippingBundle\Entity\FreightClass;
use OroB2B\Bundle\ShippingBundle\Entity\LengthUnit;
use OroB2B\Bundle\ShippingBundle\Entity\WeightUnit;

class LoadShippingOptionUnitsAndClasses extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->addWeightUnit($manager, 'kilo');
        $this->addWeightUnit($manager, 'pound');
        $this->addLengthUnits($manager, 'meter');
        $this->addLengthUnits($manager, 'ft');
        $this->addLengthUnits($manager, 'in');
        $this->addFreightClasses($manager, 'pcl');

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $code
     */
    protected function addWeightUnit(ObjectManager $manager, $code)
    {
        $entity = new WeightUnit();
        $entity->setCode($code);

        $manager->persist($entity);

        $this->addReference(sprintf('weight_unit.%s', $code), $entity);
    }

    /**
     * @param ObjectManager $manager
     * @param string $code
     */
    protected function addLengthUnits(ObjectManager $manager, $code)
    {
        $entity = new LengthUnit();
        $entity->setCode($code);

        $manager->persist($entity);

        $this->addReference(sprintf('length_unit.%s', $code), $entity);
    }

    /**
     * @param ObjectManager $manager
     * @param string $code
     */
    protected function addFreightClasses(ObjectManager $manager, $code)
    {
        $entity = new FreightClass();
        $entity->setCode($code);

        $manager->persist($entity);

        $this->addReference(sprintf('freight_class.%s', $code), $entity);
    }
}
