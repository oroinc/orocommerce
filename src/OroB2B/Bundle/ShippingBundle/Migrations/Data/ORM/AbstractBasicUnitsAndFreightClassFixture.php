<?php

namespace OroB2B\Bundle\ShippingBundle\Migrations\Data\ORM;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use OroB2B\Bundle\ShippingBundle\Entity\DimensionUnit;
use OroB2B\Bundle\ShippingBundle\Entity\FreightClass;
use OroB2B\Bundle\ShippingBundle\Entity\WeightUnit;

abstract class AbstractBasicUnitsAndFreightClassFixture extends AbstractFixture implements ContainerAwareInterface
{
    /** @var array */
    protected $weightUnits = [];

    /** @var array */
    protected $dimensionUnits = [];

    /** @var array */
    protected $freightClasses = [];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->addWeightUnits($this->weightUnits);
        $this->addDimensionUnits($this->dimensionUnits);
        $this->addFreightClasses($this->freightClasses);
    }

    /**
     * @param array $weightUnits
     */
    protected function addWeightUnits(array $weightUnits)
    {
        $class = 'OroB2BShippingBundle:WeightUnit';
        /** @var ObjectManager $manager */
        $manager = $this->container->get('doctrine')->getManagerForClass($class);

        /** @var EntityRepository $repository */
        $repository = $manager->getRepository($class);
        foreach ($weightUnits as $unit) {
            if (!$repository->findOneBy(['code' => $unit['code']])) {
                $entity = new WeightUnit();
                $entity->setCode($unit['code']);
                $entity->setConversionRates($unit['conversion_rates']);
                $manager->persist($entity);
            }
        }

        $manager->flush();
    }

    /**
     * @param array $dimensionUnits
     */
    protected function addDimensionUnits(array $dimensionUnits)
    {
        $class = 'OroB2BShippingBundle:DimensionUnit';

        /** @var ObjectManager $manager */
        $manager = $this->container->get('doctrine')->getManagerForClass($class);

        /** @var EntityRepository $repository */
        $repository = $manager->getRepository($class);
        foreach ($dimensionUnits as $unit) {
            if (!$repository->findOneBy(['code' => $unit['code']])) {
                $entity = new DimensionUnit();
                $entity->setCode($unit['code']);
                $entity->setConversionRates($unit['conversion_rates']);
                $manager->persist($entity);
            }
        }

        $manager->flush();
    }

    /**
     * @param array $freightClasses
     */
    protected function addFreightClasses(array $freightClasses)
    {
        $class = 'OroB2BShippingBundle:FreightClass';

        /** @var ObjectManager $manager */
        $manager = $this->container->get('doctrine')->getManagerForClass($class);

        /** @var EntityRepository $repository */
        $repository = $manager->getRepository($class);
        foreach ($freightClasses as $unit) {
            if (!$repository->findOneBy(['code' => $unit['code']])) {
                $entity = new FreightClass();
                $entity->setCode($unit['code']);
                $manager->persist($entity);
            }
        }

        $manager->flush();
    }
}
