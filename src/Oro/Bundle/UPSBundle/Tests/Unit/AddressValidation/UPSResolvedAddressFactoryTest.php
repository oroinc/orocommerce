<?php

declare(strict_types=1);

namespace Oro\Bundle\UPSBundle\Tests\Unit\AddressValidation;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressValidationBundle\Model\ResolvedAddress;
use Oro\Bundle\AddressValidationBundle\Tests\Unit\Stub\AddressValidatedAtAwareStub;
use Oro\Bundle\UPSBundle\AddressValidation\UPSResolvedAddressFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UPSResolvedAddressFactoryTest extends TestCase
{
    private ObjectRepository&MockObject $countryRepository;

    private ObjectRepository&MockObject $regionRepository;

    private UPSResolvedAddressFactory $factory;

    protected function setUp(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $this->countryRepository = $this->createMock(ObjectRepository::class);
        $this->regionRepository = $this->createMock(ObjectRepository::class);

        $doctrine
            ->method('getRepository')
            ->willReturnMap([
                [Country::class, null, $this->countryRepository],
                [Region::class, null, $this->regionRepository],
            ]);

        $this->factory = new UPSResolvedAddressFactory($doctrine);
    }

    public function testCreateResolvedAddressReturnsResolvedAddress(): void
    {
        $originalAddress = $this->createMock(AbstractAddress::class);
        $country = new Country('US');
        $region = new Region('CA');

        $this->countryRepository
            ->method('find')
            ->with('US')
            ->willReturn($country);
        $this->regionRepository
            ->method('findOneBy')
            ->with(['code' => 'CA', 'country' => 'US'])
            ->willReturn($region);

        $rawAddress = [
            'AddressKeyFormat' => [
                'CountryCode' => 'US',
                'PoliticalDivision1' => 'CA',
                'PoliticalDivision2' => 'San Francisco',
                'PostcodePrimaryLow' => '94103',
                'PostcodeExtendedLow' => '1234',
                'AddressLine' => ['123 Market St', 'Suite 400'],
            ],
        ];

        $resolvedAddress = $this->factory->createResolvedAddress($rawAddress, $originalAddress);

        self::assertInstanceOf(ResolvedAddress::class, $resolvedAddress);
        self::assertSame($originalAddress, $resolvedAddress->getOriginalAddress());
        self::assertSame($country, $resolvedAddress->getCountry());
        self::assertSame($region, $resolvedAddress->getRegion());
        self::assertSame('San Francisco', $resolvedAddress->getCity());
        self::assertSame('94103-1234', $resolvedAddress->getPostalCode());
        self::assertSame('123 Market St', $resolvedAddress->getStreet());
        self::assertSame('Suite 400', $resolvedAddress->getStreet2());
    }

    public function testCreateResolvedAddressReturnsNullWhenEqualsToOriginalAddress(): void
    {
        $country = new Country('US');
        $region = new Region('CA');

        $this->countryRepository
            ->method('find')
            ->with('US')
            ->willReturn($country);
        $this->regionRepository
            ->method('findOneBy')
            ->with(['code' => 'CA', 'country' => 'US'])
            ->willReturn($region);

        $rawAddress = [
            'AddressKeyFormat' => [
                'CountryCode' => 'US',
                'PoliticalDivision1' => 'CA',
                'PoliticalDivision2' => 'San Francisco',
                'PostcodePrimaryLow' => '94103',
                'PostcodeExtendedLow' => '1234',
                'AddressLine' => ['123 Market St', 'Suite 400'],
            ],
        ];

        $originalAddress = (new AddressValidatedAtAwareStub())
            ->setCountry($country)
            ->setRegion($region)
            ->setCity($rawAddress['AddressKeyFormat']['PoliticalDivision2'])
            ->setPostalCode(
                $rawAddress['AddressKeyFormat']['PostcodePrimaryLow'] . '-' .
                $rawAddress['AddressKeyFormat']['PostcodeExtendedLow']
            )
            ->setStreet($rawAddress['AddressKeyFormat']['AddressLine'][0])
            ->setStreet2($rawAddress['AddressKeyFormat']['AddressLine'][1]);

        $resolvedAddress = $this->factory->createResolvedAddress($rawAddress, $originalAddress);

        self::assertNull($resolvedAddress);
    }

    public function testCreateResolvedAddressReturnsResolvedAddressWhenNotUS(): void
    {
        $originalAddress = $this->createMock(AbstractAddress::class);
        $country = new Country('BG');
        $region = new Region('NT');

        $this->countryRepository
            ->method('find')
            ->with('BG')
            ->willReturn($country);
        $this->regionRepository
            ->method('findOneBy')
            ->with(['name' => 'Northern Thrace', 'country' => 'BG'])
            ->willReturn($region);

        $rawAddress = [
            'AddressKeyFormat' => [
                'CountryCode' => 'BG',
                'PoliticalDivision1' => 'Northern Thrace',
                'PoliticalDivision2' => 'Burgas',
                'PostcodePrimaryLow' => '98765',
                'AddressLine' => ['42 Hello St', 'Suite 12'],
            ],
        ];

        $resolvedAddress = $this->factory->createResolvedAddress($rawAddress, $originalAddress);

        self::assertInstanceOf(ResolvedAddress::class, $resolvedAddress);
        self::assertSame($country, $resolvedAddress->getCountry());
        self::assertSame($region, $resolvedAddress->getRegion());
        self::assertSame('Burgas', $resolvedAddress->getCity());
        self::assertSame('98765', $resolvedAddress->getPostalCode());
        self::assertSame('42 Hello St', $resolvedAddress->getStreet());
        self::assertSame('Suite 12', $resolvedAddress->getStreet2());
    }

    public function testCreateResolvedAddressReturnsNullWhenAddressKeyFormatIsMissing(): void
    {
        $originalAddress = $this->createMock(AbstractAddress::class);

        $resolvedAddress = $this->factory->createResolvedAddress([], $originalAddress);

        self::assertNull($resolvedAddress);
    }

    public function testCreateResolvedAddressReturnsNullWhenCountryIsInvalid(): void
    {
        $originalAddress = $this->createMock(AbstractAddress::class);

        $this->countryRepository
            ->method('find')
            ->with('XX')
            ->willReturn(null);

        $rawAddress = [
            'AddressKeyFormat' => [
                'CountryCode' => 'XX',
                'PoliticalDivision1' => 'CA',
                'PoliticalDivision2' => 'San Francisco',
                'PostcodePrimaryLow' => '94103',
                'AddressLine' => ['123 Market St'],
            ],
        ];

        $resolvedAddress = $this->factory->createResolvedAddress($rawAddress, $originalAddress);

        self::assertNull($resolvedAddress);
    }

    public function testCreateResolvedAddressReturnsNullWhenRegionIsInvalid(): void
    {
        $originalAddress = $this->createMock(AbstractAddress::class);
        $country = new Country('US');

        $this->countryRepository
            ->method('find')
            ->with('US')
            ->willReturn($country);
        $this->regionRepository
            ->method('findOneBy')
            ->willReturn(null);

        $rawAddress = [
            'AddressKeyFormat' => [
                'CountryCode' => 'US',
                'PoliticalDivision1' => 'InvalidRegion',
                'PoliticalDivision2' => 'San Francisco',
                'PostcodePrimaryLow' => '94103',
                'AddressLine' => ['123 Market St'],
            ],
        ];

        $resolvedAddress = $this->factory->createResolvedAddress($rawAddress, $originalAddress);

        self::assertNull($resolvedAddress);
    }

    public function testCreateResolvedAddressReturnsNullWhenCityIsMissing(): void
    {
        $originalAddress = $this->createMock(AbstractAddress::class);
        $country = new Country('US');
        $region = new Region('CA');

        $this->countryRepository
            ->method('find')
            ->with('US')
            ->willReturn($country);
        $this->regionRepository
            ->method('findOneBy')
            ->willReturn($region);

        $rawAddress = [
            'AddressKeyFormat' => [
                'CountryCode' => 'US',
                'PoliticalDivision1' => 'CA',
                'PostcodePrimaryLow' => '94103',
                'AddressLine' => ['123 Market St'],
            ],
        ];

        $resolvedAddress = $this->factory->createResolvedAddress($rawAddress, $originalAddress);

        self::assertNull($resolvedAddress);
    }

    public function testCreateResolvedAddressReturnsNullWhenPostalCodeIsMissing(): void
    {
        $originalAddress = $this->createMock(AbstractAddress::class);
        $country = new Country('US');
        $region = new Region('CA');

        $this->countryRepository
            ->method('find')
            ->with('US')
            ->willReturn($country);
        $this->regionRepository
            ->method('findOneBy')
            ->willReturn($region);

        $rawAddress = [
            'AddressKeyFormat' => [
                'CountryCode' => 'US',
                'PoliticalDivision1' => 'CA',
                'PoliticalDivision2' => 'San Francisco',
                'AddressLine' => ['123 Market St'],
            ],
        ];

        $resolvedAddress = $this->factory->createResolvedAddress($rawAddress, $originalAddress);

        self::assertNull($resolvedAddress);
    }

    public function testCreateResolvedAddressReturnsNullWhenStreetIsMissing(): void
    {
        $originalAddress = $this->createMock(AbstractAddress::class);
        $country = new Country('US');
        $region = new Region('CA');

        $this->countryRepository
            ->method('find')
            ->with('US')
            ->willReturn($country);
        $this->regionRepository
            ->method('findOneBy')
            ->willReturn($region);

        $rawAddress = [
            'AddressKeyFormat' => [
                'CountryCode' => 'US',
                'PoliticalDivision1' => 'CA',
                'PoliticalDivision2' => 'San Francisco',
                'PostcodePrimaryLow' => '94103',
            ],
        ];

        $resolvedAddress = $this->factory->createResolvedAddress($rawAddress, $originalAddress);

        self::assertNull($resolvedAddress);
    }
}
