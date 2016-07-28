<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleDestination;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;

class ShippingRuleDestinationTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    /**
     * @var Region
     */
    protected $region;

    /**
     * @var Country
     */
    protected $country;

    /**
     * @var ShippingRuleDestination
     */
    protected $shippingRuleDestination;

    public function setUp()
    {
        $this->country = $this->createMockCountry();
        $this->region = $this->createMockRegion();
        $this->shippingRuleDestination = $this->getEntity(
            'OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleDestination',
            [
                'region' => $this->region,
                'country' => $this->country,
                'postalCode' => '12345',
            ]
        );
    }

    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['postalCode', 'fr4a'],
            ['region', new Region('code')],
            ['country', new Country('UA')],
            ['shippingRule', new ShippingRule()],
        ];

        $this->assertPropertyAccessors(new ShippingRuleDestination(), $properties);
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
     *
     * @param array $data
     * @param string $expectedString
     */
    public function testToString(array $data, $expectedString)
    {
        $entity = (string)$this->getEntity('OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleDestination', $data);
        $this->assertEquals($expectedString, $entity);
    }

    /**
     * @return array
     */
    public function toStringDataProvider()
    {
        return [
            'all' => [
                'data' => [
                    'country' => $this->createMockCountry(),
                    'region' => $this->createMockRegion(),
                    'postalCode' => '12345',
                ],
                'expectedString' => 'RegionName, CountryName 12345'
            ],
            'country and postal code' => [
                'data' => [
                    'country' => $this->createMockCountry(),
                    'region' => null,
                    'postalCode' => '12345, 54321',
                ],
                'expectedString' => 'CountryName 12345, 54321'
            ],
            'country and region' => [
                'data' => [
                    'country' => $this->createMockCountry('SecondCountryName'),
                    'region' => $this->createMockRegion('SecondRegionName'),
                    'postalCode' => null,
                ],
                'expectedString' => 'SecondRegionName, SecondCountryName'
            ],
            'only country' => [
                'data' => [
                    'country' => $this->createMockCountry(),
                    'region' => null,
                    'postalCode' => null,
                ],
                'expectedString' => 'CountryName'
            ]
        ];
    }

    /**
     * @param string $name
     * @param string $iso2
     * @param string $iso3
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createMockCountry($name = 'CountryName', $iso2 = 'CountryIso2', $iso3 = 'CountryIso3')
    {
        $result = $this->getMockBuilder('Oro\Bundle\AddressBundle\Entity\Country')
            ->disableOriginalConstructor()
            ->getMock();
        $result->expects($this->any())
            ->method('__toString')
            ->will($this->returnValue($name));
        $result->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));
        $result->expects($this->any())
            ->method('getIso2Code')
            ->will($this->returnValue($iso2));
        $result->expects($this->any())
            ->method('getIso3Code')
            ->will($this->returnValue($iso3));

        return $result;
    }

    /**
     * @param string $name
     * @param string $code
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createMockRegion($name = 'RegionName', $code = 'RegionCode')
    {
        $result = $this->getMock('Oro\Bundle\AddressBundle\Entity\Region', [], ['combinedCode']);
        $result->expects($this->any())
            ->method('__toString')
            ->will($this->returnValue($name));
        $result->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));
        $result->expects($this->any())
            ->method('getCode')
            ->will($this->returnValue($code));
        return $result;
    }
}
