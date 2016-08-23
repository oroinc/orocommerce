<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Factory;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Factory\TaxBaseExclusionFactory;
use Oro\Bundle\TaxBundle\Model\TaxBaseExclusion;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class TaxBaseExclusionFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TaxBaseExclusionFactory
     */
    protected $factory;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->factory = new TaxBaseExclusionFactory($this->doctrineHelper);
    }

    protected function tearDown()
    {
        unset($this->factory, $this->doctrineHelper);
    }

    /**
     * @dataProvider createProvider
     * @param array $value
     * @param TaxBaseExclusion $expected
     */
    public function testCreate($value, $expected)
    {
        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityReference')
            ->willReturnCallback(function ($entityClass, $id) {
                if (strpos($entityClass, 'Country') !== false) {
                    return new Country($id);
                }

                if (strpos($entityClass, 'Region') !== false) {
                    return new Region($id);
                }

                return null;
            });

        $this->assertEquals($expected, $this->factory->create($value));
    }

    /**
     * @return array
     */
    public function createProvider()
    {
        return [
            'country, region and option' => [
                'value' => [
                    'country' => 'US',
                    'region' => 'US-AL',
                    'option' => TaxationSettingsProvider::USE_AS_BASE_DESTINATION,
                ],
                'expected' => new TaxBaseExclusion(
                    [
                        'country' => new Country('US'),
                        'region' => new Region('US-AL'),
                        'option' => TaxationSettingsProvider::USE_AS_BASE_DESTINATION,
                    ]
                ),
            ],
            'country and option' => [
                'value' => [
                    'country' => 'US',
                    'option' => TaxationSettingsProvider::USE_AS_BASE_DESTINATION,
                ],
                'expected' => new TaxBaseExclusion(
                    [
                        'country' => new Country('US'),
                        'option' => TaxationSettingsProvider::USE_AS_BASE_DESTINATION,
                    ]
                ),
            ],
        ];
    }
}
