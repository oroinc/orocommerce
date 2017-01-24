<?php

namespace Oro\Bundle\DPDBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadShippingServicesData extends AbstractFixture implements ContainerAwareInterface
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
        $this->loadServices($manager);
    }

    /**
     * @param ObjectManager $manager
     *
     * @throws \InvalidArgumentException
     */
    public function loadServices(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroDPDBundle/Migrations/Data/ORM/data/dpd_services.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'rb');
        $headers = fgetcsv($handler, 1000, ',');

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $shippingService = new ShippingService();
            $shippingService
                ->setCode($row['code'])
                ->setDescription($row['description']);

            $manager->persist($shippingService);
        }
        fclose($handler);

        $manager->flush();
    }
}
