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
    private FieldHelper|\PHPUnit\Framework\MockObject\MockObject $fieldHelper;

    private ConfigProvider|\PHPUnit\Framework\MockObject\MockObject $configProvider;

    private LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject $localizationHelper;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $doctrine;

    private ProductExportNormalizer $productNormalizer;

    protected function setUp(): void
    {
        $this->fieldHelper = $this->createMock(FieldHelper::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->productNormalizer = new ProductExportNormalizer(
            $this->fieldHelper,
            $this->configProvider,
            $this->localizationHelper,
            $this->doctrine
        );
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
                        'name' => 'type',
                        'type' => 'string',
                        'label' => 'type',
                    ],
                ]
            );

        $this->configProvider->expects(self::exactly(2))
            ->method('getConfig')
            ->willReturnMap(
                [
                    [Product::class, 'sku', $this->getConfig(Product::class, ['use_in_export' => true])],
                    [Product::class, 'type', $this->getConfig(Product::class, [])],
                ]
            );

        $this->fieldHelper->expects(self::once())
            ->method('getObjectValue')
            ->willReturnMap(
                [
                    [$product, 'sku', 'SKU-1'],
                ]
            );

        $productName = new ProductName();
        $productName->setString('Test Name');

        $this->localizationHelper->expects(self::once())
            ->method('getLocalizedValue')
            ->willReturn($productName);

        $this->localizationHelper->expects(self::once())
            ->method('getCurrentLocalization')
            ->willReturn(new Localization());

        $result = $this->productNormalizer->normalize($product);
        self::assertArrayHasKey('sku', $result);
        self::assertArrayNotHasKey('type', $result);
        self::assertArrayHasKey('names', $result);
        self::assertEquals('SKU-1', $result['sku']);
        self::assertEquals('Test Name', (string)$result['names']);
    }

    public function testNormalizeWithLocaleInOptions(): void
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
                ]
            );

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturnMap(
                [
                    [Product::class, 'sku', $this->getConfig(Product::class, ['use_in_export' => true])],
                ]
            );

        $this->fieldHelper->expects(self::once())
            ->method('getObjectValue')
            ->willReturnMap(
                [
                    [$product, 'sku', 'SKU-1'],
                ]
            );

        $productName = new ProductName();
        $productName->setString('Test Name');

        $this->localizationHelper->expects(self::once())
            ->method('getLocalizedValue')
            ->willReturn($productName);

        $repository = $this->createMock(LocalizationRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->willReturn(new Localization());

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->localizationHelper->expects(self::never())
            ->method('getCurrentLocalization');

        $result = $this->productNormalizer->normalize($product, null, ['currentLocalizationId' => 1]);
        self::assertArrayHasKey('sku', $result);
        self::assertArrayHasKey('names', $result);
        self::assertEquals('SKU-1', $result['sku']);
        self::assertEquals('Test Name', (string)$result['names']);
    }

    public function testNormalizeIfLocaleInOptionsNotFound(): void
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
                ]
            );

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturnMap(
                [
                    [Product::class, 'sku', $this->getConfig(Product::class, ['use_in_export' => true])],
                ]
            );

        $this->fieldHelper->expects(self::once())
            ->method('getObjectValue')
            ->willReturnMap(
                [
                    [$product, 'sku', 'SKU-1'],
                ]
            );

        $productName = new ProductName();
        $productName->setString('Test Name');

        $this->localizationHelper->expects(self::once())
            ->method('getLocalizedValue')
            ->willReturn($productName);

        $repository = $this->createMock(LocalizationRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->willReturn(null);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->localizationHelper->expects(self::once())
            ->method('getCurrentLocalization')
            ->willReturn(new Localization());

        $result = $this->productNormalizer->normalize($product, null, ['currentLocalizationId' => 1]);
        self::assertArrayHasKey('sku', $result);
        self::assertArrayHasKey('names', $result);
        self::assertEquals('SKU-1', $result['sku']);
        self::assertEquals('Test Name', (string)$result['names']);
    }

    public function testNormalizeWithoutEnabledAttributes(): void
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
                ]
            );

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturnMap(
                [
                    [Product::class, 'sku', $this->getConfig(Product::class, [])],
                ]
            );

        $this->fieldHelper->expects(self::never())
            ->method('getObjectValue');

        $productName = new ProductName();
        $productName->setString('Test Name');

        $this->localizationHelper->expects(self::once())
            ->method('getLocalizedValue')
            ->willReturn($productName);

        $repository = $this->createMock(LocalizationRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->willReturn(new Localization());

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $result = $this->productNormalizer->normalize($product, null, ['currentLocalizationId' => 1]);
        self::assertArrayNotHasKey('sku', $result);
        self::assertArrayHasKey('names', $result);
        self::assertEquals('Test Name', (string)$result['names']);
    }

    /**
     * @param mixed $data
     * @param null $format
     * @param array $context
     * @param bool $expected
     *
     * @dataProvider normalizationDataProvider
     */
    public function testSupportsNormalization($data, $format, array $context, bool $expected): void
    {
        self::assertEquals($expected, $this->productNormalizer->supportsNormalization($data, $format, $context));
    }

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

    public function testSupportsDenormalization(): void
    {
        self::assertFalse(
            $this->productNormalizer->supportsDenormalization(
                new Product(),
                '',
                null,
                [
                    'processorAlias' => 'oro_product_frontend_product_listing',
                ]
            )
        );
    }

    private function getConfig(string $className, array $values): Config
    {
        return new Config(
            new EntityConfigId('attribute', $className),
            $values
        );
    }
}
