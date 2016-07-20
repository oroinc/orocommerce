<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingDestination;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;

class ShippingDestinationTest extends \PHPUnit_Framework_TestCase
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
     * @var ShippingDestination
     */
    protected $shippingDestination;
    
    public function setUp()
    {
        $this->country = $this->createMockCountry();
        $this->region = $this->createMockRegion();
        $this->shippingDestination = $this->getEntity(
            'OroB2B\Bundle\ShippingBundle\Entity\ShippingDestination',
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

        $this->assertPropertyAccessors(new ShippingDestination(), $properties);
    }

    public function testGetRegionName()
    {
        $this->assertEquals('RegionName', $this->shippingDestination->getRegionName());
        $this->shippingDestination->setRegion(null);
        $this->assertEquals('', $this->shippingDestination->getRegionName());
    }

    public function testGetRegionCode()
    {
        $this->assertEquals('RegionCode', $this->shippingDestination->getRegionCode());
        $this->shippingDestination->setRegion(null);
        $this->assertEquals('', $this->shippingDestination->getRegionCode());
    }

    public function testGetCountryName()
    {
        $this->assertEquals('CountryName', $this->shippingDestination->getCountryName());
        $this->shippingDestination->setCountry(null);
        $this->assertEquals('', $this->shippingDestination->getCountryName());
    }

    public function testGetCountryIso2()
    {
        $this->assertEquals('CountryIso2', $this->shippingDestination->getCountryIso2());
        $this->shippingDestination->setCountry(null);
        $this->assertEquals('', $this->shippingDestination->getCountryIso2());
    }

    public function testGetCountryIso3()
    {
        $this->assertEquals('CountryIso3', $this->shippingDestination->getCountryIso3());
        $this->shippingDestination->setCountry(null);
        $this->assertEquals('', $this->shippingDestination->getCountryIso3());
    }

    public function testToString()
    {
        $this->assertEquals('RegionName , CountryName 12345', (string)$this->shippingDestination);
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
        $result = $this->getMock('Oro\Bundle\AddressBundle\Entity\Region', array(), array('combinedCode'));
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
