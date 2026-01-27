<?php

namespace Oro\Bundle\UPSBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Migrations\Data\ORM\LoadCountryData;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads shipping services.
 */
class LoadShippingServicesData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    private array $loadedCountries = [];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadCountryData::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->loadSpecifiedCountryServices($manager);
        $this->loadEUCountriesServices($manager);
        $this->loadUnspecifiedCountryServices($manager);
    }

    private function loadSpecifiedCountryServices(ObjectManager $manager): void
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroUPSBundle/Migrations/Data/ORM/data/specified_country_services.csv');
        if (\is_array($filePath)) {
            $filePath = current($filePath);
        }

        $countryRepository = $manager->getRepository(Country::class);

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            /** @var Country $country */
            $country = $countryRepository->findOneBy(['iso2Code' => $row['country']]);
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

    private function loadEUCountriesServices(ObjectManager $manager): void
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroUPSBundle/Migrations/Data/ORM/data/eu_countries_services.csv');
        if (\is_array($filePath)) {
            $filePath = current($filePath);
        }

        $countryRepository = $manager->getRepository(Country::class);

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');
        $countries = array_keys(self::getEUCountries());
        $services = [];
        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $services[] = array_combine($headers, array_values($data));
        }
        foreach ($countries as $countryCode) {
            if (!in_array($countryCode, $this->loadedCountries, true)) {
                /** @var Country $country */
                $country = $countryRepository->findOneBy(['iso2Code' => $countryCode]);
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

    private function loadUnspecifiedCountryServices(ObjectManager $manager): void
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroUPSBundle/Migrations/Data/ORM/data/unspecified_country_services.csv');
        if (\is_array($filePath)) {
            $filePath = current($filePath);
        }

        $countries = $manager->getRepository(Country::class)->findAll();

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            /** @var Country $country */
            foreach ($countries as $country) {
                if (
                    $row['type'] === 'AC' ||
                    ($row['type'] === 'UC' && !in_array($country->getIso2Code(), $this->loadedCountries, false))
                ) {
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

    private static function getEUCountries(): array
    {
        return [
            'AT' => 'Austria',
            'BE' => 'Belgium',
            'BG' => 'Bulgaria',
            'HR' => 'Croatia',
            'CY' => 'Cyprus',
            'CZ' => 'Czechia',
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
    }
}
