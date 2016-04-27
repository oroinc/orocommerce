<?php

namespace OroB2B\Bundle\ShippingBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use OroB2B\Bundle\ShippingBundle\Entity\DimensionUnit;
use OroB2B\Bundle\ShippingBundle\Entity\FreightClass;
use OroB2B\Bundle\ShippingBundle\Entity\WeightUnit;

abstract class AbstractBasicUnitsAndFreightClassFixture extends AbstractFixture
{

    /** @var array */
    protected $weightUnits = [];

    /** @var array */
    protected $dimensionUnits = [];

    /** @var array */
    protected $freightClasses = [];

    /** @var ObjectManager */
    protected $manager;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;

        $this->addWeightUnits($this->weightUnits);
        $this->addDimensionUnits($this->dimensionUnits);
        $this->addFreightClasses($this->freightClasses);

        $this->manager->flush();
    }

    /**
     * @param string $class
     *
     * @return ObjectRepository
     */
    protected function getRepositoryForClass($class)
    {
        return $this->manager->getRepository($class);
    }

    /**
     * @param array $weightUnits
     */
    protected function addWeightUnits(array $weightUnits)
    {
        $class = 'OroB2BShippingBundle:WeightUnit';

        /** @var ObjectRepository $repository */
        $repository = $this->getRepositoryForClass($class);
        foreach ($weightUnits as $unit) {
            if (!$repository->findOneBy(['code' => $unit['code']])) {
                $entity = new WeightUnit();
                $entity->setCode($unit['code'])
                    ->setConversionRates($unit['conversion_rates'])
                ;
                $this->manager->persist($entity);
            }
        }
    }

    /**
     * @param array $dimensionUnits
     */
    protected function addDimensionUnits(array $dimensionUnits)
    {
        $class = 'OroB2BShippingBundle:DimensionUnit';

        /** @var ObjectRepository $repository */
        $repository = $this->getRepositoryForClass($class);
        foreach ($dimensionUnits as $unit) {
            if (!$repository->findOneBy(['code' => $unit['code']])) {
                $entity = new DimensionUnit();
                $entity->setCode($unit['code'])
                    ->setConversionRates($unit['conversion_rates'])
                ;
                $this->manager->persist($entity);
            }
        }
    }

    /**
     * @param array $freightClasses
     */
    protected function addFreightClasses(array $freightClasses)
    {
        $class = 'OroB2BShippingBundle:FreightClass';

        /** @var ObjectRepository $repository */
        $repository = $this->getRepositoryForClass($class);
        foreach ($freightClasses as $unit) {
            if (!$repository->findOneBy(['code' => $unit['code']])) {
                $entity = new FreightClass();
                $entity->setCode($unit['code']);
                $this->manager->persist($entity);
            }
        }
    }
}
