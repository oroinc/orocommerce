<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Normalizer;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ImportExportBundle\Field\FieldHelper;
use Oro\Bundle\ProductBundle\ImportExport\Normalizer\ProductNormalizer;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductNormalizer
     */
    protected $productNormalizer;

    /**
     * @var FieldHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldHelper;

    /**
     * @var string
     */
    protected $productClass;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    protected function setUp()
    {
        $this->fieldHelper = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Field\FieldHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->productClass = 'Oro\Bundle\ProductBundle\Entity\Product';
        $this->productNormalizer = new ProductNormalizer($this->fieldHelper);
        $this->productNormalizer->setProductClass($this->productClass);
        $this->productNormalizer->setEventDispatcher($this->eventDispatcher);
    }

    public function testNormalize()
    {
        $product = new Product();

        $this->fieldHelper->expects($this->once())
            ->method('getFields')
            ->willReturn(
                [
                    [
                        'name' => 'sku',
                        'type' => 'string',
                        'label' => 'sku',
                    ],
                ]
            );

        $this->fieldHelper->expects($this->once())
            ->method('getObjectValue')
            ->will(
                $this->returnValueMap(
                    [
                        [$product, 'sku', 'SKU-1'],
                    ]
                )
            );

        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->withConsecutive(
                [
                    $this->logicalAnd(
                        $this->isType('string'),
                        $this->equalTo('orob2b_product.normalizer.normalizer')
                    ),
                    $this->isInstanceOf('Oro\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent'),
                ]
            );

        $result = $this->productNormalizer->normalize($product);
        $this->assertArrayHasKey('sku', $result);
        $this->assertEquals($result['sku'], 'SKU-1');
    }

    public function testDenormalize()
    {
        $data = ['sku' => 'SKU-1'];

        $this->fieldHelper->expects($this->once())
            ->method('getFields')
            ->willReturn(
                [
                    [
                        'name' => 'sku',
                        'type' => 'string',
                        'label' => 'sku',
                    ],
                ]
            );

        $this->fieldHelper->expects($this->once())
            ->method('setObjectValue')
            ->will(
                $this->returnCallback(
                    function (Product $result, $fieldName, $value) {
                        return $result->{'set' . ucfirst($fieldName)}($value);
                    }
                )
            );

        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->withConsecutive(
                [
                    $this->logicalAnd(
                        $this->isType('string'),
                        $this->equalTo('orob2b_product.normalizer.denormalizer')
                    ),
                    $this->isInstanceOf('Oro\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent'),
                ]
            );

        $result = $this->productNormalizer->denormalize($data, $this->productClass);
        $this->assertInstanceOf($this->productClass, $result);
        $this->assertEquals($result->getSku(), 'SKU-1');
    }

    /**
     * @param mixed $data
     * @param bool $expected
     *
     * @dataProvider normalizationDataProvider
     */
    public function testSupportsNormalization($data, $expected)
    {
        $this->assertEquals($expected, $this->productNormalizer->supportsNormalization($data));
    }

    /**
     * @return array
     */
    public function normalizationDataProvider()
    {
        return [
            [false, false],
            [true, false],
            ['', false],
            ['string', false],
            [[], false],
            [['array'], false],
            [new \stdClass(), false],
            [new Product(), true],
        ];
    }

    /**
     * @param string $type
     * @param bool $expected
     *
     * @dataProvider denormalizationDataProvider
     */
    public function testSupportsDenormalization($type, $expected)
    {
        $this->assertEquals($expected, $this->productNormalizer->supportsDenormalization([], $type));
    }

    /**
     * @return array
     */
    public function denormalizationDataProvider()
    {
        return [
            ['\stdClass', false],
            ['string', false],
            ['', false],
            ['Oro\Bundle\ProductBundle\Entity\Product', true],
        ];
    }
}
