<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Duplicator;

use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\ProductBundle\Duplicator\SkuIncrementor;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class SkiIncrementorTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_CLASS = 'OroB2BProductBundle:Product';

    /**
     * @var SkuIncrementor
     */
    protected $service;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $manager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->service = new SkuIncrementor($this->manager, self::PRODUCT_CLASS);
    }

    /**
     * @dataProvider skuDataProvider
     * @param string[] $existingSku
     * @param array $testCases
     */
    public function testIncrementSku(array $existingSku, array $testCases)
    {
        $this->manager
            ->expects($this->any())
            ->method('getRepository')
            ->with(self::PRODUCT_CLASS)
            ->willReturn($this->getProductRepositoryMock($existingSku));

        foreach ($testCases as $expected => $sku) {
            $this->assertEquals($expected, $this->service->increment($sku));
        }
    }

    /**
     * @return array
     */
    public function skuDataProvider()
    {
        return [
            [
                ['ABC123', 'ABC123-66', 'ABC123-77', 'ABC123-88', 'ABC123-89abc'],
                [
                    'ABC123-89' => 'ABC123-77',
                    'ABC123-90' => 'ABC123-77',
                    'ABC123-91' => 'ABC123-66'
                ]
            ],
            [
                ['DEF123-66', 'DEF123-88'],
                [
                    'DEF123-66-1' => 'DEF123-66',
                    'DEF123-66-2' => 'DEF123-66',
                    'DEF123-88-1' => 'DEF123-88',
                    'DEF123-88-2' => 'DEF123-88',
                ]
            ],
        ];
    }

    private function getProductRepositoryMock($existingSku)
    {
        $mock = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $mock
            ->expects($this->any())
            ->method('findAllSkuByPattern')
            ->withAnyParameters()
            ->willReturn($existingSku);

        $mock
            ->expects($this->any())
            ->method('findOneBySku')
            ->willReturnCallback(function ($sku) use ($existingSku) {
                return in_array($sku, $existingSku) ? new Product() : null;
            });

        return $mock;
    }
}
