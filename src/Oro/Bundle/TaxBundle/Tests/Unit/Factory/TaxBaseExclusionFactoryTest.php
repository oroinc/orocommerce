<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Factory;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Factory\TaxBaseExclusionFactory;
use Oro\Bundle\TaxBundle\Model\TaxBaseExclusion;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class TaxBaseExclusionFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var TaxBaseExclusionFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->factory = new TaxBaseExclusionFactory($this->doctrineHelper);
    }

    /**
     * @dataProvider createProvider
     */
    public function testCreate(array $value, TaxBaseExclusion $expected)
    {
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->willReturnCallback(function ($entityClass, $id) {
                if (str_contains($entityClass, 'Country')) {
                    return new Country($id);
                }

                if (str_contains($entityClass, 'Region')) {
                    return new Region($id);
                }

                return null;
            });

        $this->assertEquals($expected, $this->factory->create($value));
    }

    public function createProvider(): array
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
