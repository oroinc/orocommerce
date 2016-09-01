<?php

namespace Oro\Bundle\UPSBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\AddressBundle\Entity\Country;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\UPSBundle\Entity\ShippingService;

class LoadShippingServicesData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    const EU = [
        'AT' => 'Austria',
        'BE' => 'Belgium',
        'BG' => 'Bulgaria',
        'HR' => 'Croatia',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic',
        'DK' => 'Denmark',
        'EE' => 'Estonia',
        'FI' => 'Finland',
        'FR' => 'France',
        'DE' => 'Germany',
        'GR' => 'Greece',
        'HU' => 'Hungary',
        'IE' => 'Ireland',
        'IT' => 'Italy',
        'LV' => 'Latvia',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'MT' => 'Malta',
        'NL' => 'Netherlands',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'RO' => 'Romania',
        'SK' => 'Slovakia',
        'SI' => 'Slovenia',
        'ES' => 'Spain',
        'SE' => 'Sweden',
        'GB' => 'United Kingdom'
    ];

    /**
     * @var EntityRepository
     */
    protected $countryRepository;

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
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\AddressBundle\Migrations\Data\ORM\LoadCountryData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->countryRepository = $manager->getRepository('OroAddressBundle:Country');
        $this->loadSpecifiedCountryServices($manager);
        $this->loadEUCountriesServices($manager);
        $this->loadUnspecifiedCountryServices($manager);
    }

    /**
     * @param ObjectManager $manager
     */
    public function loadSpecifiedCountryServices(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroUPSBundle/Migrations/Data/ORM/data/specified_country_services.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            /** @var Country $country */
            $country = $this->countryRepository->findOneBy(['iso2Code' => $row['country']]);
            $shippingService = new ShippingService();
            $shippingService
                ->setCode($row['code'])
                ->setDescription($row['description'])
                ->setCountry($country);

            $manager->persist($shippingService);
            $this->loadedCountries[$country->getIso2Code()] = $country->getIso2Code();
        }
        fclose($handler);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     */
    public function loadEUCountriesServices(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroUPSBundle/Migrations/Data/ORM/data/eu_countries_services.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');
        $countries = array_keys(static::EU);
        $services = [];
        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $services[] = array_combine($headers, array_values($data));
        }
        foreach ($countries as $countryCode) {
            if (!in_array($countryCode, $this->loadedCountries, true)) {
                /** @var Country $country */
                $country = $this->countryRepository->findOneBy(['iso2Code' => $countryCode]);
                foreach ($services as $row) {
                    $shippingService = new ShippingService();
                    $shippingService
                        ->setCode($row['code'])
                        ->setDescription($row['description'])
                        ->setCountry($country);

                    $manager->persist($shippingService);
                }
                $this->loadedCountries[$country->getIso2Code()] = $country->getIso2Code();
            }
        }
        fclose($handler);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     */
    public function loadUnspecifiedCountryServices(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroUPSBundle/Migrations/Data/ORM/data/unspecified_country_services.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');
        $countries = $this->countryRepository->findAll();

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            /** @var Country $country */
            foreach ($countries as $country) {
                if (($row['type'] === 'UC' && !in_array($country->getIso2Code(), $this->loadedCountries, false)) ||
                    $row['type'] === 'AC') {
                    $shippingService = new ShippingService();
                    $shippingService
                        ->setCode($row['code'])
                        ->setDescription($row['description'])
                        ->setCountry($country);

                    $manager->persist($shippingService);
                }
            }
        }
        fclose($handler);

        $manager->flush();
    }
}
