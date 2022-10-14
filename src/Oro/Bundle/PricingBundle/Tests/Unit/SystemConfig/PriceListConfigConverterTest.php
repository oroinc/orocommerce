<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SystemConfig;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;

class PriceListConfigConverterTest extends \PHPUnit\Framework\TestCase
{
    use ConfigsGeneratorTrait;

    public function testConvertBeforeSave()
    {
        $converter = new PriceListConfigConverter($this->createMock(ManagerRegistry::class), '\PriceList');
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
        $converter = new PriceListConfigConverter($this->getDoctrine(), '\PriceList');

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

        $converter = new PriceListConfigConverter($this->getDoctrine(), '\PriceList');

        $configs = [
            ['priceList' => 1, 'sort_order' => 100, 'mergeAllowed' => true],
            ['priceList' => 5, 'sort_order' => 500, 'mergeAllowed' => false]
        ];

        $converter->convertFromSaved($configs);
    }

    private function getDoctrine(): ManagerRegistry
    {
        $priceListConfigs = $this->createConfigs(2);
        $priceLists = array_map(function (PriceListConfig $item) {
            return $item->getPriceList();
        }, $priceListConfigs);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->once())
            ->method('findBy')
            ->willReturn($priceLists);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($manager);

        return $doctrine;
    }
}
