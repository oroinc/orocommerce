<?php

declare(strict_types=1);

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\AddressValidation;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressValidationBundle\Model\ResolvedAddress;
use Oro\Bundle\AddressValidationBundle\Tests\Unit\Stub\AddressValidatedAtAwareStub;
use Oro\Bundle\FedexShippingBundle\AddressValidation\FedexResolvedAddressFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class FedexResolvedAddressFactoryTest extends TestCase
{
    private ObjectRepository&MockObject $countryRepository;

    private ObjectRepository&MockObject $regionRepository;

    private FedexResolvedAddressFactory $factory;

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

        $this->factory = new FedexResolvedAddressFactory($doctrine);
    }

    public function testCreateResolvedAddressReturnsResolvedAddress(): void
    {
        $country = new Country('US');
        $region = new Region('CA');
        $originalAddress = $this->createMock(AbstractAddress::class);

        $this->countryRepository
            ->method('find')
            ->with('US')
            ->willReturn($country);

        $this->regionRepository
            ->method('findOneBy')
            ->with(['code' => 'CA', 'country' => 'US'])
            ->willReturn($region);

        $rawAddress = [
            'countryCode' => 'US',
            'stateOrProvinceCode' => 'CA',
            'city' => 'San Francisco',
            'parsedPostalCode' => [
                'base' => '94103',
                'addOn' => '1234',
            ],
            'streetLinesToken' => ['123 Market St', 'Suite 400'],
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
            'countryCode' => 'US',
            'stateOrProvinceCode' => 'CA',
            'city' => 'San Francisco',
            'parsedPostalCode' => [
                'base' => '94103',
                'addOn' => '1234',
            ],
            'streetLinesToken' => ['123 Market St', 'Suite 400'],
        ];

        $originalAddress = (new AddressValidatedAtAwareStub())
            ->setCountry($country)
            ->setRegion($region)
            ->setCity($rawAddress['city'])
            ->setPostalCode($rawAddress['parsedPostalCode']['base'] . '-' . $rawAddress['parsedPostalCode']['addOn'])
            ->setStreet($rawAddress['streetLinesToken'][0])
            ->setStreet2($rawAddress['streetLinesToken'][1]);

        $resolvedAddress = $this->factory->createResolvedAddress($rawAddress, $originalAddress);

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
            'countryCode' => 'XX',
            'stateOrProvinceCode' => 'CA',
            'city' => 'San Francisco',
            'parsedPostalCode' => ['base' => '94103'],
            'streetLinesToken' => ['123 Market St'],
        ];

        $resolvedAddress = $this->factory->createResolvedAddress($rawAddress, $originalAddress);

        self::assertNull($resolvedAddress);
    }

    public function testCreateResolvedAddressReturnsNullWhenRegionIsInvalid(): void
    {
        $country = new Country('US');

        $this->countryRepository
            ->method('find')
            ->with('US')
            ->willReturn($country);
        $this->regionRepository
            ->method('findOneBy')
            ->willReturn(null);

        $rawAddress = [
            'countryCode' => 'US',
            'stateOrProvinceCode' => 'XX',
            'city' => 'San Francisco',
            'parsedPostalCode' => ['base' => '94103'],
            'streetLinesToken' => ['123 Market St'],
        ];

        $resolvedAddress = $this->factory->createResolvedAddress(
            $rawAddress,
            $this->createMock(AbstractAddress::class)
        );

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
            'countryCode' => 'US',
            'stateOrProvinceCode' => 'CA',
            'parsedPostalCode' => ['base' => '94103'],
            'streetLinesToken' => ['123 Market St'],
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
            'countryCode' => 'US',
            'stateOrProvinceCode' => 'CA',
            'city' => 'San Francisco',
            'parsedPostalCode' => [],
            'streetLinesToken' => ['123 Market St'],
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
            'countryCode' => 'US',
            'stateOrProvinceCode' => 'CA',
            'city' => 'San Francisco',
            'parsedPostalCode' => ['base' => '94103'],
            'streetLinesToken' => [],
        ];

        $resolvedAddress = $this->factory->createResolvedAddress($rawAddress, $originalAddress);

        self::assertNull($resolvedAddress);
    }
}
