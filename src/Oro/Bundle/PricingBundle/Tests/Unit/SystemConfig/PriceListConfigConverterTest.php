<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SystemConfig;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;
use PHPUnit\Framework\MockObject\MockObject;

class PriceListConfigConverterTest extends \PHPUnit\Framework\TestCase
{
    use ConfigsGeneratorTrait;

    public function testConvertBeforeSave()
    {
        $converter = new PriceListConfigConverter($this->getRegistryMock(), '\PriceList');
        $testData = $this->createConfigs(2);

        $expected = [
            ['priceList' => 1, 'sort_order' => 100, 'mergeAllowed' => true],
            ['priceList' => 2, 'sort_order' => 200, 'mergeAllowed' => false]
        ];

        $actual = $converter->convertBeforeSave($testData);
        $this->assertSame($expected, $actual);
    }

    public function testConvertFromSaved()
    {
        $registry = $this->getRegistryMockWithRepository();
        $converter = new PriceListConfigConverter($registry, '\PriceList');

        $configs = [
            ['priceList' => 1, 'sort_order' => 100, 'mergeAllowed' => true],
            ['priceList' => 2, 'sort_order' => 200, 'mergeAllowed' => false]
        ];

        $actual = $converter->convertFromSaved($configs);

        $convertedConfigs = $this->createConfigs(2);
        $expected = [$convertedConfigs[0], $convertedConfigs[1]];

        $this->assertEquals($expected, $actual);
    }

    public function testConvertFromSavedInvalidData()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Price list record with id 5 not found, while reading');

        $registry = $this->getRegistryMockWithRepository();
        $converter = new PriceListConfigConverter($registry, '\PriceList');

        $configs = [
            ['priceList' => 1, 'sort_order' => 100, 'mergeAllowed' => true],
            ['priceList' => 5, 'sort_order' => 500, 'mergeAllowed' => false]
        ];

        $converter->convertFromSaved($configs);
    }

    /**
     * @return MockObject|ManagerRegistry
     */
    protected function getRegistryMock()
    {
        return $this->createMock('Doctrine\Persistence\ManagerRegistry');
    }

    /**
     * @return MockObject|ManagerRegistry
     */
    protected function getRegistryMockWithRepository()
    {
        $priceListConfigs = $this->createConfigs(2);
        $priceLists = array_map(function ($item) {
            /** @var PriceListConfig $item */
            return $item->getPriceList();
        }, $priceListConfigs);

        $repository = $this->getMockBuilder('\Doctrine\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('findBy')
            ->willReturn($priceLists);

        $manager = $this->getMockBuilder('\Doctrine\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $registry = $this->getRegistryMock();

        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($manager);

        return $registry;
    }
}
