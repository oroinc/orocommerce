<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Factory;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Factory\AddressModelFactory;
use Oro\Bundle\TaxBundle\Model\Address;

class AddressModelFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @var AddressModelFactory
     */
    protected $factory;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->factory = new AddressModelFactory($this->doctrineHelper);
    }

    protected function tearDown()
    {
        unset($this->factory, $this->doctrineHelper);
    }

    /**
     * @dataProvider createProvider
     * @param array $values
     * @param Address $expected
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
            'country and region' => [
                'values' => [
                    'country' => 'US',
                    'region' => 'US-AL',
                ],
                'expected' => (new Address())->setCountry(new Country('US'))->setRegion(new Region('US-AL'))
            ],
            'country only' => [
                'values' => [
                    'country' => 'US',
                ],
                'expected' => (new Address())->setCountry(new Country('US'))
            ],
            'without anything' => [
                'values' => [],
                'expected' => new Address()
            ],
        ];
    }
}
