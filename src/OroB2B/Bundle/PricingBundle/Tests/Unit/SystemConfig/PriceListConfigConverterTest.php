<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\SystemConfig;

use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigBag;
use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\Common\Collections\ArrayCollection;

class PriceListConfigConverterTest extends \PHPUnit_Framework_TestCase
{
    use ConfigsGeneratorTrait;

    public function testConvertBeforeSave()
    {
        /** @var RegistryInterface $registry */
        $registry = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');

        $converter = new PriceListConfigConverter($registry, '\PriceList');
        $bag = new PriceListConfigBag();

        $bag->setConfigs(new ArrayCollection($this->createConfigs(2)));

        $expected = [
            ['priceList' => 1, 'priority' => 100],
            ['priceList' => 2, 'priority' => 200]
        ];

        $actual = $converter->convertBeforeSave($bag);

        $this->assertSame($expected, $actual);
    }

    public function testConvertFromSaved()
    {
        $priceListConfigs = $this->createConfigs(2);
        $priceLists = array_map(function ($item) {
            /** @var PriceListConfig $item */
            return $item->getPriceList();
        }, $priceListConfigs);

        $repository = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('findBy')
            ->willReturn($priceLists);

        $manager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        /** @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMockBuilder('Symfony\Bridge\Doctrine\RegistryInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $converter = new PriceListConfigConverter($registry, '\PriceList');

        $configs = [
            ['priceList' => 1, 'priority' => 100],
            ['priceList' => 2, 'priority' => 200]
        ];

        $expected = new PriceListConfigBag();
        $expected->setConfigs(new ArrayCollection($priceListConfigs));

        $actual = $converter->convertFromSaved($configs);

        $this->assertEquals($expected, $actual);
    }
}
