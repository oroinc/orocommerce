<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Factory;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Factory\AddressModelFactory;
use Oro\Bundle\TaxBundle\Model\Address;

class AddressModelFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var AddressModelFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->factory = new AddressModelFactory($this->doctrineHelper);
    }

    /**
     * @dataProvider createProvider
     */
    public function testCreate(array $values, Address $expected)
    {
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->willReturnCallback(function ($classAlias, $id) {
                if (str_contains($classAlias, 'Country')) {
                    return new Country($id);
                }
                if (str_contains($classAlias, 'Region')) {
                    return new Region($id);
                }

                return null;
            });

        $this->assertEquals($expected, $this->factory->create($values));
    }

    public function createProvider(): array
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
