<?php

namespace Oro\Bundle\DPDBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class LoadShippingServicesData extends AbstractShippingServiceFixture implements ContainerAwareInterface
{
    /**
     * @var array
     */
    protected $loadedCountries;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     */
    public function load(ObjectManager $manager)
    {
        $this->addUpdateShippingServices($manager, $this->getShippingServicesData());
        $manager->flush();
    }

    /**
     * @return array
     */
    protected function getShippingServicesData()
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/dpd_services.yml'));
    }
}
