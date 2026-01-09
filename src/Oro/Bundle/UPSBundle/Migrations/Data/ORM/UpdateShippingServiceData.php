<?php

namespace Oro\Bundle\UPSBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Migrations\Data\ORM\LoadCountryData;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Updates shipping services.
 */
class UpdateShippingServiceData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    private array $allCountryCodes;

    private array $euCountryCodes;

    private array $specifiedCountryCodes = [];

    #[\Override]
    public function getDependencies()
    {
        return [LoadCountryData::class];
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $this->allCountryCodes = array_column($this->getAllCountryCodes(), "iso2Code");
        $this->euCountryCodes = $this->getEUCountryCodes();
        $this->loadAllShippingServices($manager);
        $this->loadEuShippingServices($manager);
        $this->loadSpecifiedShippingServices($manager);
        $this->loadUnspecifiedShippingServices($manager);
    }

    private function loadEuShippingServices(ObjectManager $manager): void
    {
        $this->iterateCsvFile(
            'eu_countries_services.csv',
            function (array $row) use ($manager) {
                if (!in_array($row['country'], $this->euCountryCodes)) {
                    return;
                }
                $shippingService = $this->getShippingServiceByIsoAndCode($row['country'], $row['code']);
                if ($shippingService) {
                    $shippingService->setDescription($row['description']);
                } else {
                    $shippingService = $this->createShippingService($row);
                }
                $manager->persist($shippingService);
            }
        );

        $manager->flush();
    }

    private function loadSpecifiedShippingServices(ObjectManager $manager): void
    {
        $this->iterateCsvFile(
            'specified_country_services.csv',
            function (array $row) use ($manager) {
                if (!in_array($row['country'], $this->specifiedCountryCodes)) {
                    $this->specifiedCountryCodes[] = $row['country'];
                }
                $shippingService = $this->getShippingServiceByIsoAndCode($row['country'], $row['code']);
                if ($shippingService) {
                    $shippingService->setDescription($row['description']);
                } else {
                    $shippingService = $this->createShippingService($row);
                }
                $manager->persist($shippingService);
            }
        );

        $manager->flush();
    }

    private function loadUnspecifiedShippingServices(ObjectManager $manager): void
    {
        $this->iterateCsvFile(
            'unspecified_country_services.csv',
            function (array $row) use ($manager) {
                foreach ($this->allCountryCodes as $countryCode) {
                    if (in_array($countryCode, $this->specifiedCountryCodes)) {
                        return;
                    }
                    $shippingService = $this->getShippingServiceByIsoAndCode($countryCode, $row['code']);
                    if ($shippingService) {
                        $shippingService->setDescription($row['description']);
                    } else {
                        $row['country'] = $countryCode;
                        $shippingService = $this->createShippingService($row);
                    }
                    $manager->persist($shippingService);
                }
            }
        );

        $manager->flush();
    }

    private function loadAllShippingServices(ObjectManager $manager): void
    {
        $this->iterateCsvFile(
            'all_country_services.csv',
            function (array $row) use ($manager) {
                foreach ($this->allCountryCodes as $countryCode) {
                    $shippingService = $this->getShippingServiceByIsoAndCode($countryCode, $row['code']);
                    if ($shippingService) {
                        $shippingService->setDescription($row['description']);
                    } else {
                        $row['country'] = $countryCode;
                        $shippingService = $this->createShippingService($row);
                    }
                    $manager->persist($shippingService);
                }
            }
        );

        $manager->flush();
    }

    private function iterateCsvFile(string $fileName, \Closure $callback): void
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate(
            sprintf('@OroUPSBundle/Migrations/Data/ORM/data/%s', $fileName)
        );

        if (\is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler);

        while (($data = fgetcsv($handler)) !== false) {
            $row = array_combine($headers, array_values($data));

            $callback($row);
        }

        fclose($handler);
    }

    private function createShippingService(array $row): ShippingService
    {
        $shippingService = new ShippingService();
        $shippingService
            ->setCode($row['code'])
            ->setDescription($row['description'])
            ->setCountry($this->getCountryByIsoCode($row['country']));
        return $shippingService;
    }

    private function getShippingServiceByIsoAndCode(string $countryIsoCode, string $serviceCode): ?ShippingService
    {
        $em = $this->container->get('doctrine')->getManager();
        return $em
            ->getRepository(ShippingService::class)
            ->findOneBy(['country' => $countryIsoCode, 'code' => $serviceCode]);
    }

    private function getCountryByIsoCode(string $isoCode): Country
    {
        $em = $this->container->get('doctrine')->getManagerForClass(Country::class);
        return $em->getRepository(Country::class)->findOneBy(['iso2Code' => $isoCode]);
    }

    private function getAllCountryCodes(): array
    {
        $em = $this->container->get('doctrine')->getManagerForClass(Country::class);
        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();

        $qb
            ->select('c.iso2Code')
            ->from(Country::class, 'c');

        return $qb->getQuery()->getArrayResult();
    }

    private function getEUCountryCodes(): array
    {
        $countries = [
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

        return array_keys($countries);
    }
}
