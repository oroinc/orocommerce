<?php

namespace OroB2B\Bundle\ShippingBundle\Bundle\Tests\Unit\Factory;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ShippingBundle\Factory\ShippingOriginModelFactory;
use OroB2B\Bundle\ShippingBundle\Model\ShippingOrigin;

class ShippingOriginModelFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var ShippingOriginModelFactory */
    protected $factory;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->factory = new ShippingOriginModelFactory($this->doctrineHelper);
    }

    protected function tearDown()
    {
        unset($this->factory, $this->doctrineHelper);
    }

    /**
     * @dataProvider createProvider
     *
     * @param array          $values
     * @param ShippingOrigin $expected
     */
    public function testCreate($values, $expected)
    {
        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityReference')
            ->willReturnCallback(
                function ($classAlias, $id) {
                    if (strpos($classAlias, 'Country')) {
                        return new Country($id);
                    }
                    if (strpos($classAlias, 'Region')) {
                        return new Region($id);
                    }

                    return null;
                }
            );
        $this->assertEquals($expected, $this->factory->create($values));
    }

    /**
     * @return array
     */
    public function createProvider()
    {
        return [
            'all' => [
                'values' => [
                    'country' => 'US',
                    'region' => 'US-AL',
                    'city' => 'New York',
                    'street' => 'Street 1',
                    'street2' => 'Street 2',
                ],
                'expected' => (new ShippingOrigin())
                        ->setCountry(new Country('US'))
                        ->setRegion(new Region('US-AL'))
                        ->setCity('New York')
                        ->setStreet('Street 1')
                        ->setStreet2('Street 2')
            ],
            'country and region' => [
                'values' => [
                    'country' => 'US',
                    'region' => 'US-AL',
                ],
                'expected' => (new ShippingOrigin())->setCountry(new Country('US'))->setRegion(new Region('US-AL'))
            ],
            'country only' => [
                'values' => [
                    'country' => 'US',
                ],
                'expected' => (new ShippingOrigin())->setCountry(new Country('US'))
            ],
            'without anything' => [
                'values' => [],
                'expected' => new ShippingOrigin()
            ],
        ];
    }
}
