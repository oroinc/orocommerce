<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Duplicator;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ProductBundle\Duplicator\SkuIncrementor;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class SkuIncrementorTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_CLASS = 'OroB2BProductBundle:Product';

    /**
     * @var SkuIncrementor
     */
    protected $service;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->service = new SkuIncrementor($this->doctrineHelper, self::PRODUCT_CLASS);
    }

    /**
     * @dataProvider skuDataProvider
     * @param string[] $existingSku
     * @param array $testCases
     */
    public function testIncrementSku(array $existingSku, array $testCases)
    {
        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityRepository')
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
