<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ScalarFieldDenormalizer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Normalizer\ProductNormalizer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;

class ProductNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductNormalizer
     */
    protected $productNormalizer;

    /**
     * @var FieldHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $fieldHelper;

    /**
     * @var string
     */
    protected $productClass;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $eventDispatcher;

    protected function setUp(): void
    {
        $this->fieldHelper = $this->getMockBuilder(FieldHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->productClass = Product::class;
        $this->productNormalizer = new ProductNormalizer($this->fieldHelper);
        $this->productNormalizer->setProductClass($this->productClass);
        $this->productNormalizer->setEventDispatcher($this->eventDispatcher);
        $this->productNormalizer->setScalarFieldDenormalizer(new ScalarFieldDenormalizer());
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
                    $this->isInstanceOf(ProductNormalizerEvent::class),
                    $this->logicalAnd(
                        $this->isType('string'),
                        $this->equalTo('oro_product.normalizer.normalizer')
                    ),
                ]
            );

        $result = $this->productNormalizer->normalize($product);
        $this->assertArrayHasKey('sku', $result);
        $this->assertEquals('SKU-1', $result['sku']);
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
                    $this->isInstanceOf(ProductNormalizerEvent::class),
                    $this->logicalAnd(
                        $this->isType('string'),
                        $this->equalTo('oro_product.normalizer.denormalizer')
                    ),
                ]
            );

        $result = $this->productNormalizer->denormalize($data, $this->productClass);
        $this->assertInstanceOf($this->productClass, $result);
        $this->assertEquals('SKU-1', $result->getSku());
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
            [\stdClass::class, false],
            ['string', false],
            ['', false],
            [Product::class, true],
        ];
    }
}
