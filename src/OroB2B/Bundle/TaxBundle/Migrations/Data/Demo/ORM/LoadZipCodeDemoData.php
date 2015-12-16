<?php

namespace OroB2B\Bundle\TaxBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\TaxBundle\Entity\ZipCode;

class LoadZipCodeDemoData extends AbstractFixture implements
    FixtureInterface,
    ContainerAwareInterface
{
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
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BTaxBundle/Migrations/Data/Demo/ORM/data/zip_codes.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        $taxCodes = [];
        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $code = empty($row['code']) ? null : $row['code'];
            $rangeStart = empty($row['range_start']) ? null : $row['range_start'];
            $rangeEnd = empty($row['range_end']) ? null : $row['range_end'];

            $zipCode = new ZipCode();
            $zipCode->setZipCode($code)
                ->setZipRangeStart($rangeStart)
                ->setZipRangeEnd($rangeEnd);

            $manager->persist($zipCode);
            $taxCodes[] = $zipCode;
        }

        fclose($handler);
        $manager->flush();
    }
}
