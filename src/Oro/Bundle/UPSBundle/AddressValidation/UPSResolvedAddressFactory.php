<?php

declare(strict_types=1);

namespace Oro\Bundle\UPSBundle\AddressValidation;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressValidationBundle\Model\ResolvedAddress;
use Oro\Bundle\AddressValidationBundle\ResolvedAddress\Factory\ResolvedAddressFactoryInterface;

/**
 * Creates {@see ResolvedAddress} for the specified raw address data coming from an address validation response.
 *
 * @see https://developer.ups.com/tag/Address-Validation
 */
class UPSResolvedAddressFactory implements ResolvedAddressFactoryInterface
{
    public function __construct(private ManagerRegistry $doctrine)
    {
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function createResolvedAddress(array $rawAddress, AbstractAddress $originalAddress): ?ResolvedAddress
    {
        if (!isset($rawAddress['AddressKeyFormat'])) {
            // Address format is invalid.
            return null;
        }

        $addressKeyFormat = $rawAddress['AddressKeyFormat'];

        $country = $this->findCountry($addressKeyFormat);
        if ($country === null) {
            // Address entity without a country is not acceptable.
            return null;
        }

        $region = $this->findRegion($addressKeyFormat, $country);
        if ($region === null) {
            // Address entity without a region is not acceptable.
            return null;
        }

        if (empty($addressKeyFormat['PoliticalDivision2'])) {
            // Address entity without a city is not acceptable.
            return null;
        }

        $postalCode = $this->getFullPostalCode($addressKeyFormat);
        if (empty($postalCode)) {
            // Address entity without a postal code is not acceptable.
            return null;
        }

        if (empty($addressKeyFormat['AddressLine'])) {
            // Address entity without a street is not acceptable.
            return null;
        }

        $addressLines = (array)$addressKeyFormat['AddressLine'];

        $resolvedAddress = (new ResolvedAddress($originalAddress))
            ->setCountry($country)
            ->setRegion($region)
            ->setCity((string)$addressKeyFormat['PoliticalDivision2'])
            ->setPostalCode($postalCode)
            ->setStreet((string)array_shift($addressLines));

        if (count($addressLines)) {
            $resolvedAddress->setStreet2((string)array_shift($addressLines));
        }

        if ($this->isEqualToOriginalAddress($resolvedAddress)) {
            // No sense in returning the address which equals to the original address.
            return null;
        }

        return $resolvedAddress;
    }

    private function getFullPostalCode(array $addressKeyFormat): string
    {
        $segments = [];

        if (isset($addressKeyFormat['PostcodePrimaryLow'])) {
            // Low-end Postal Code. Returned for countries or territories with Postal Codes. May be alphanumeric.
            $segments[] = $addressKeyFormat['PostcodePrimaryLow'];
        }

        if (isset($addressKeyFormat['PostcodeExtendedLow'])) {
            // Low-end extended postal code in a range.
            // Example: 30076-1234, where '1234' is a low-end extended postal code. May be alphanumeric.
            $segments[] = $addressKeyFormat['PostcodeExtendedLow'];
        }

        return implode('-', $segments);
    }

    private function findCountry(array $rawAddress): ?Country
    {
        if (empty($rawAddress['CountryCode'])) {
            return null;
        }

        return $this->doctrine->getRepository(Country::class)->find($rawAddress['CountryCode']);
    }

    private function findRegion(array $rawAddress, Country $country): ?Region
    {
        if (empty($rawAddress['PoliticalDivision1'])) {
            return null;
        }

        $repository = $this->doctrine->getRepository(Region::class);

        // For Domestic addresses, the value must be a valid 2-character value (per US Mail standards).
        if ($country->getIso2Code() === 'US') {
            return $repository->findOneBy([
                'code' => $rawAddress['PoliticalDivision1'],
                'country' => $country->getIso2Code(),
            ]);
        }

        // For International the full State or Province name will be returned.
        return $repository->findOneBy([
            'name' => $rawAddress['PoliticalDivision1'],
            'country' => $country->getIso2Code(),
        ]);
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
