<?php

declare(strict_types=1);

namespace Oro\Bundle\FedexShippingBundle\AddressValidation;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressValidationBundle\Model\ResolvedAddress;
use Oro\Bundle\AddressValidationBundle\ResolvedAddress\Factory\ResolvedAddressFactoryInterface;

/**
 * Creates {@see ResolvedAddress} for the specified raw address data coming from an address validation response.
 *
 * @see https://developer.fedex.com/api/en-us/catalog/address-validation/v1/docs.html
 */
class FedexResolvedAddressFactory implements ResolvedAddressFactoryInterface
{
    public function __construct(private ManagerRegistry $doctrine)
    {
    }

    public function createResolvedAddress(array $rawAddress, AbstractAddress $originalAddress): ?ResolvedAddress
    {
        $country = $this->findCountry($rawAddress['countryCode'] ?? '');
        if ($country === null) {
            // Address entity without a country is not acceptable.
            return null;
        }

        $region = $this->findRegion($rawAddress['stateOrProvinceCode'] ?? '', $country);
        if ($region === null) {
            // Address entity without a region is not acceptable.
            return null;
        }

        if (empty($rawAddress['city'])) {
            // Address entity without a city is not acceptable.
            return null;
        }

        $postalCode = $this->getFullPostalCode($rawAddress['parsedPostalCode']);
        if (empty($postalCode)) {
            // Address entity without a postal code is not acceptable.
            return null;
        }

        if (empty($rawAddress['streetLinesToken'])) {
            // Address entity without a street is not acceptable.
            return null;
        }

        $streetLines = (array)$rawAddress['streetLinesToken'];

        $resolvedAddress = (new ResolvedAddress($originalAddress))
            ->setCountry($country)
            ->setRegion($region)
            ->setCity((string)$rawAddress['city'])
            ->setPostalCode($postalCode)
            ->setStreet((string)array_shift($streetLines));

        if (count($streetLines)) {
            $resolvedAddress->setStreet2((string)array_shift($streetLines));
        }

        if ($this->isEqualToOriginalAddress($resolvedAddress)) {
            // No sense in returning the address which equals to the original address.
            return null;
        }

        return $resolvedAddress;
    }

    private function getFullPostalCode(array $parsedPostalCode): string
    {
        $segments = [];

        if (isset($parsedPostalCode['base'])) {
            $segments[] = $parsedPostalCode['base'];
        }

        if (isset($parsedPostalCode['addOn'])) {
            $segments[] = $parsedPostalCode['addOn'];
        }

        if (isset($parsedPostalCode['deliveryPoint'])) {
            $segments[] = $parsedPostalCode['deliveryPoint'];
        }

        return implode('-', $segments);
    }

    private function findCountry(string $iso2Code): ?Country
    {
        return $this->doctrine->getRepository(Country::class)->find($iso2Code);
    }

    private function findRegion(string $region, Country $country): ?Region
    {
        return $this->doctrine
            ->getRepository(Region::class)
            ->findOneBy(['code' => $region, 'country' => $country->getIso2Code()]);
    }

    private function isEqualToOriginalAddress(ResolvedAddress $resolvedAddress): bool
    {
        $originalAddress = $resolvedAddress->getOriginalAddress();

        return $resolvedAddress->getCountry() === $originalAddress->getCountry() &&
            $resolvedAddress->getRegion() === $originalAddress->getRegion() &&
            $resolvedAddress->getCity() === $originalAddress->getCity() &&
            $resolvedAddress->getPostalCode() === $originalAddress->getPostalCode() &&
            $resolvedAddress->getStreet() === $originalAddress->getStreet() &&
            $resolvedAddress->getStreet2() === $originalAddress->getStreet2();
    }
}
