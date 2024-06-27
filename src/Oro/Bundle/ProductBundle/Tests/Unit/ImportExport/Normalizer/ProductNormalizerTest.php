<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ScalarFieldDenormalizer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;
use Oro\Bundle\ProductBundle\ImportExport\Normalizer\ProductNormalizer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductNormalizerTest extends \PHPUnit\Framework\TestCase
{
    private ProductNormalizer $productNormalizer;

    private FieldHelper|\PHPUnit\Framework\MockObject\MockObject $fieldHelper;

    private string $productClass;

    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

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

    public function testNormalize(): void
    {
        $product = new Product();

        $this->fieldHelper->expects(self::once())
            ->method('getEntityFields')
            ->willReturn(
                [
                    [
                        'name' => 'sku',
                        'type' => 'string',
                        'label' => 'sku',
                    ],
                    [
                        'name' => 'unitPrecisions',
                        'type' => 'array',
                        'label' => 'unitPrecisions',
                    ]
                ]
            );

        $this->fieldHelper
            ->expects(self::any())
            ->method('getObjectValue')
            ->willReturnMap([
                [$product, 'sku', 'SKU-1'],
                [$product, 'unitPrecisions', [['unit' => ['code' => 'kg']]]],
            ]);

        $this->eventDispatcher->expects(self::once())->method('dispatch')
            ->withConsecutive(
                [
                    self::isInstanceOf(ProductNormalizerEvent::class),
                    self::logicalAnd(
                        self::isType('string'),
                        self::equalTo('oro_product.normalizer.normalizer')
                    ),
                ]
            );

        $result = $this->productNormalizer->normalize($product);
        self::assertArrayHasKey('sku', $result);
        self::assertArrayHasKey('additionalUnitPrecisions', $result);
        self::assertEquals('SKU-1', $result['sku']);
        self::assertEquals([['unit' => ['code' => 'kg']]], $result['additionalUnitPrecisions']);
    }

    public function testDenormalize(): void
    {
        $data = ['sku' => 'SKU-1'];

        $this->fieldHelper->expects(self::once())
            ->method('getEntityFields')
            ->willReturn(
                [
                    [
                        'name' => 'sku',
                        'type' => 'string',
                        'label' => 'sku',
                    ],
                ]
            );

        $this->fieldHelper->expects(self::once())
            ->method('setObjectValue')
            ->willReturnCallback(
                function (Product $result, $fieldName, $value) {
                    return $result->{'set' . ucfirst($fieldName)}($value);
                }
            );

        $this->eventDispatcher->expects(self::once())->method('dispatch')
            ->withConsecutive(
                [
                    self::isInstanceOf(ProductNormalizerEvent::class),
                    self::logicalAnd(
                        self::isType('string'),
                        self::equalTo('oro_product.normalizer.denormalizer')
                    ),
                ]
            );

        $result = $this->productNormalizer->denormalize($data, $this->productClass);
        self::assertInstanceOf($this->productClass, $result);
        self::assertEquals('SKU-1', $result->getSku());
    }

    /**
     * @param mixed $data
     * @param bool $expected
     *
     * @dataProvider normalizationDataProvider
     */
    public function testSupportsNormalization($data, $expected): void
    {
        self::assertEquals($expected, $this->productNormalizer->supportsNormalization($data));
    }

    /**
     * @return array
     */
    public function normalizationDataProvider(): array
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
    public function testSupportsDenormalization($type, $expected): void
    {
        self::assertEquals($expected, $this->productNormalizer->supportsDenormalization([], $type));
    }

    /**
     * @return array
     */
    public function denormalizationDataProvider(): array
    {
        return [
            [\stdClass::class, false],
            ['string', false],
            ['', false],
            [Product::class, true],
        ];
    }
}
