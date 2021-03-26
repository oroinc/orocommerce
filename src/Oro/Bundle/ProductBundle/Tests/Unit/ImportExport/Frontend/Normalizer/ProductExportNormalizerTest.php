<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Frontend\Normalizer;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\ImportExport\Frontend\Normalizer\ProductExportNormalizer;

class ProductExportNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FieldHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private FieldHelper $fieldHelper;

    /**
     * @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private ConfigProvider $attributeConfigProvider;

    /**
     * @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private LocalizationHelper $localizationHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry
     */
    private ManagerRegistry $doctrine;

    private ProductExportNormalizer $productNormalizer;

    protected function setUp(): void
    {
        $this->fieldHelper = $this->createMock(FieldHelper::class);
        $this->attributeConfigProvider = $this->createMock(ConfigProvider::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->productNormalizer = new ProductExportNormalizer(
            $this->fieldHelper,
            $this->attributeConfigProvider,
            $this->localizationHelper,
            $this->doctrine
        );
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
                    [
                        'name' => 'type',
                        'type' => 'string',
                        'label' => 'type'
                    ]
                ]
            );

        $this->attributeConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnMap(
                [
                    [Product::class, 'sku', $this->getConfig(Product::class, ['use_in_export' => true])],
                    [Product::class, 'type', $this->getConfig(Product::class, [])],
                ]
            );

        $this->fieldHelper->expects($this->once())
            ->method('getObjectValue')
            ->willReturnMap(
                [
                    [$product, 'sku', 'SKU-1'],
                ]
            );

        $productName = new ProductName();
        $productName->setString('Test Name');

        $this->localizationHelper->expects($this->once())
            ->method('getLocalizedValue')
            ->willReturn($productName);

        $this->localizationHelper->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn(new Localization());

        $result = $this->productNormalizer->normalize($product);
        $this->assertArrayHasKey('sku', $result);
        $this->assertArrayNotHasKey('type', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertEquals($result['sku'], 'SKU-1');
        $this->assertEquals((string)$result['name'], 'Test Name');
    }

    public function testNormalizeWithLocaleInOptions()
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
                    ]
                ]
            );

        $this->attributeConfigProvider->expects($this->once())
            ->method('getConfig')
            ->willReturnMap(
                [
                    [Product::class, 'sku', $this->getConfig(Product::class, ['use_in_export' => true])],
                ]
            );

        $this->fieldHelper->expects($this->once())
            ->method('getObjectValue')
            ->willReturnMap(
                [
                    [$product, 'sku', 'SKU-1'],
                ]
            );

        $productName = new ProductName();
        $productName->setString('Test Name');

        $this->localizationHelper->expects($this->once())
            ->method('getLocalizedValue')
            ->willReturn($productName);

        $repository = $this->createMock(LocalizationRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->willReturn(new Localization());

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->localizationHelper->expects($this->never())
            ->method('getCurrentLocalization');

        $result = $this->productNormalizer->normalize($product, null, ['currentLocalizationId' => 1]);
        $this->assertArrayHasKey('sku', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertEquals($result['sku'], 'SKU-1');
        $this->assertEquals((string)$result['name'], 'Test Name');
    }

    public function testNormalizeIfLocaleInOptionsNotFound()
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
                    ]
                ]
            );

        $this->attributeConfigProvider->expects($this->once())
            ->method('getConfig')
            ->willReturnMap(
                [
                    [Product::class, 'sku', $this->getConfig(Product::class, ['use_in_export' => true])],
                ]
            );

        $this->fieldHelper->expects($this->once())
            ->method('getObjectValue')
            ->willReturnMap(
                [
                    [$product, 'sku', 'SKU-1'],
                ]
            );

        $productName = new ProductName();
        $productName->setString('Test Name');

        $this->localizationHelper->expects($this->once())
            ->method('getLocalizedValue')
            ->willReturn($productName);

        $repository = $this->createMock(LocalizationRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->localizationHelper->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn(new Localization());

        $result = $this->productNormalizer->normalize($product, null, ['currentLocalizationId' => 1]);
        $this->assertArrayHasKey('sku', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertEquals($result['sku'], 'SKU-1');
        $this->assertEquals((string)$result['name'], 'Test Name');
    }

    public function testNormalizeWithoutEnabledAttributes()
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
                    ]
                ]
            );

        $this->attributeConfigProvider->expects($this->once())
            ->method('getConfig')
            ->willReturnMap(
                [
                    [Product::class, 'sku', $this->getConfig(Product::class, [])],
                ]
            );

        $this->fieldHelper->expects($this->never())
            ->method('getObjectValue');

        $productName = new ProductName();
        $productName->setString('Test Name');

        $this->localizationHelper->expects($this->once())
            ->method('getLocalizedValue')
            ->willReturn($productName);

        $repository = $this->createMock(LocalizationRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->willReturn(new Localization());

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $result = $this->productNormalizer->normalize($product, null, ['currentLocalizationId' => 1]);
        $this->assertArrayNotHasKey('sku', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertEquals((string)$result['name'], 'Test Name');
    }

    /**
     * @param mixed $data
     * @param null $format
     * @param array $context
     * @param bool $expected
     *
     * @dataProvider normalizationDataProvider
     */
    public function testSupportsNormalization($data, $format, array $context, bool $expected)
    {
        $this->assertEquals($expected, $this->productNormalizer->supportsNormalization($data, $format, $context));
    }

    /**
     * @return array
     */
    public function normalizationDataProvider(): array
    {
        return [
            [false, null, [], false],
            [true, null, [], false],
            ['', null, [], false],
            ['string', null, [], false],
            [[], null, [], false],
            [['array'], null, [], false],
            [new \stdClass(), null, [], false],
            [new Product(), null, [], false],
            [new Product(), null, ['processorAlias' => 'test_processor'], false],
            [new Product(), null, ['processorAlias' => 'oro_product_frontend_product_listing'], true],
        ];
    }

    public function testSupportsDenormalization()
    {
        $this->assertFalse($this->productNormalizer->supportsDenormalization(new Product(), null, [
            'processorAlias' => 'oro_product_frontend_product_listing'
        ]));
    }

    private function getConfig(string $className, array $values): Config
    {
        return new Config(
            new EntityConfigId('attribute', $className),
            $values
        );
    }
}
