<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRuleDestination;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRuleDestinationPostalCode;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class ShippingMethodsConfigsRuleDestinationTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    /** @var ShippingMethodsConfigsRuleDestination */
    private $shippingRuleDestination;

    protected function setUp(): void
    {
        $this->shippingRuleDestination = $this->getEntity(
            ShippingMethodsConfigsRuleDestination::class,
            [
                'region' => $this->getRegion(),
                'country' => $this->getCountry(),
                'postalCodes' => new ArrayCollection([$this->getPostalCode('123')]),
            ]
        );
    }

    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['region', new Region('code')],
            ['regionText', 'text'],
            ['country', new Country('UA')],
            ['methodConfigsRule', new ShippingMethodsConfigsRule()],
        ];

        $destination = new ShippingMethodsConfigsRuleDestination();
        self::assertPropertyAccessors($destination, $properties);
        self::assertPropertyCollection($destination, 'postalCodes', $this->getPostalCode('123'));
    }

    public function testGetRegionName()
    {
        $this->assertEquals('RegionName', $this->shippingRuleDestination->getRegionName());
        $this->shippingRuleDestination->setRegion(null);
        $this->assertEquals('', $this->shippingRuleDestination->getRegionName());
    }

    public function testGetRegionCode()
    {
        $this->assertEquals('RegionCode', $this->shippingRuleDestination->getRegionCode());
        $this->shippingRuleDestination->setRegion(null);
        $this->assertEquals('', $this->shippingRuleDestination->getRegionCode());
    }

    public function testGetCountryName()
    {
        $this->assertEquals('CountryName', $this->shippingRuleDestination->getCountryName());
        $this->shippingRuleDestination->setCountry(null);
        $this->assertEquals('', $this->shippingRuleDestination->getCountryName());
    }

    public function testGetCountryIso2()
    {
        $this->assertEquals('CountryIso2', $this->shippingRuleDestination->getCountryIso2());
        $this->shippingRuleDestination->setCountry(null);
        $this->assertEquals('', $this->shippingRuleDestination->getCountryIso2());
    }

    public function testGetCountryIso3()
    {
        $this->assertEquals('CountryIso3', $this->shippingRuleDestination->getCountryIso3());
        $this->shippingRuleDestination->setCountry(null);
        $this->assertEquals('', $this->shippingRuleDestination->getCountryIso3());
    }

    /**
     * @dataProvider toStringDataProvider
     */
    public function testToString(array $data, string $expectedString)
    {
        $entity = (string) $this->getEntity(
            ShippingMethodsConfigsRuleDestination::class,
            $data
        );
        $this->assertEquals($expectedString, $entity);
    }

    public function toStringDataProvider(): array
    {
        return [
            'all' => [
                'data' => [
                    'country' => $this->getCountry(),
                    'region' => $this->getRegion(),
                    'postalCodes' => new ArrayCollection([$this->getPostalCode('12345')]),
                ],
                'expectedString' => 'RegionName, CountryName 12345'
            ],
            'country and postal code' => [
                'data' => [
                    'country' => $this->getCountry(),
                    'region' => null,
                    'postalCodes' => new ArrayCollection([
                        $this->getPostalCode('12345'),
                        $this->getPostalCode('54321'),
                    ]),
                ],
                'expectedString' => 'CountryName 12345, 54321'
            ],
            'country and region' => [
                'data' => [
                    'country' => $this->getCountry('SecondCountryName'),
                    'region' => $this->getRegion('SecondRegionName'),
                    'postalCodes' => new ArrayCollection(),
                ],
                'expectedString' => 'SecondRegionName, SecondCountryName'
            ],
            'only country' => [
                'data' => [
                    'country' => $this->getCountry(),
                    'region' => null,
                    'postalCodes' => new ArrayCollection(),
                ],
                'expectedString' => 'CountryName'
            ]
        ];
    }

    private function getCountry(
        string $name = 'CountryName',
        string $iso2 = 'CountryIso2',
        string $iso3 = 'CountryIso3'
    ): Country {
        $result = $this->createMock(Country::class);
        $result->expects($this->any())
            ->method('__toString')
            ->willReturn($name);
        $result->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $result->expects($this->any())
            ->method('getIso2Code')
            ->willReturn($iso2);
        $result->expects($this->any())
            ->method('getIso3Code')
            ->willReturn($iso3);

        return $result;
    }

    private function getRegion(string $name = 'RegionName', string $code = 'RegionCode'): Region
    {
        $result = $this->createMock(Region::class);
        $result->expects($this->any())
            ->method('__toString')
            ->willReturn($name);
        $result->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $result->expects($this->any())
            ->method('getCode')
            ->willReturn($code);
        return $result;
    }

    private function getPostalCode(string $name): ShippingMethodsConfigsRuleDestinationPostalCode
    {
        return (new ShippingMethodsConfigsRuleDestinationPostalCode())->setName($name);
    }
}
