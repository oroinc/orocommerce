<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\QuickAdd\Normalizer;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Model\QuickAddField;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\Provider\FrontendProductUnitsProvider;
use Oro\Bundle\ProductBundle\QuickAdd\Normalizer\BasicQuickAddCollectionNormalizer;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class BasicQuickAddCollectionNormalizerTest extends TestCase
{
    private BasicQuickAddCollectionNormalizer $normalizer;
    private FrontendProductUnitsProvider $frontendProductUnitsProvider;

    #[\Override]
    protected function setUp(): void
    {
        $localizationHelper = $this->createMock(LocalizationHelper::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $unitLabelFormatter = $this->createMock(UnitLabelFormatterInterface::class);
        $unitLabelFormatter->expects(self::any())
            ->method('format')
            ->willReturnCallback(static fn (string $code) => $code . ' [label]');

        $this->frontendProductUnitsProvider = $this->createMock(FrontendProductUnitsProvider::class);
        $this->frontendProductUnitsProvider->expects(self::any())
            ->method('getUnitsForProduct')
            ->willReturnCallback(static fn (Product $product) => $product->getSellUnitsPrecision());

        $this->normalizer = new BasicQuickAddCollectionNormalizer(
            $localizationHelper,
            $unitLabelFormatter,
            $translator,
            $this->frontendProductUnitsProvider
        );

        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(static function (string $key, array $parameters, string $domain) {
                self::assertEquals('validators', $domain);

                return $key . ' [trans]';
            });

        $localizationHelper->expects(self::any())
            ->method('getLocalizedValue')
            ->willReturnCallback(static function (Collection $collection) {
                return $collection[0]->getString();
            });
    }

    public function testNormalizerUsesUnitsFromProvider(): void
    {
        $productName = new ProductName();
        $productName->setString('Sample Name');

        $each = new ProductUnit();
        $each->setCode('each');
        $primaryPrecision = new ProductUnitPrecision();
        $primaryPrecision->setUnit($each);
        $primaryPrecision->setPrecision(0);

        $product = new ProductStub();
        $product->setSku('SKU1');
        $product->addName($productName);
        $product->addUnitPrecision($primaryPrecision);

        $filteredUnits = ['each' => 0];
        $this->frontendProductUnitsProvider->expects(self::once())
            ->method('getUnitsForProduct')
            ->with($product)
            ->willReturn($filteredUnits);

        $quickAddRow = new QuickAddRow(1, $product->getSku(), 5, $each->getCode());
        $quickAddRow->setProduct($product);

        $result = $this->normalizer->normalize(new QuickAddRowCollection([$quickAddRow]));

        self::assertSame($filteredUnits, $result['items'][1]['units']);
    }

    public function testGetResultsWhenEmpty(): void
    {
        $quickAddRowCollection = new QuickAddRowCollection();

        self::assertEquals(['errors' => [], 'items' => []], $this->normalizer->normalize($quickAddRowCollection));
    }

    /**
     * @dataProvider getResultsDataProvider
     */
    public function testGetResults(QuickAddRowCollection $quickAddRowCollection, array $expected): void
    {
        self::assertEquals($expected, $this->normalizer->normalize($quickAddRowCollection));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getResultsDataProvider(): array
    {
        $quickAddRowWithoutProductAndAdditional = new QuickAddRow(1, 'SKU1', 42, 'item');

        $productName = new ProductName();
        $productName->setString('Sample Name');
        $each = new ProductUnit();
        $each->setCode('each');
        $productUnitPrecision = new ProductUnitPrecision();
        $productUnitPrecision->setUnit($each);
        $productUnitPrecision->setPrecision(1);
        $product = new ProductStub();
        $product->setSku('SKU2');
        $product->addName($productName);
        $product->addUnitPrecision($productUnitPrecision);
        $quickAddRowWithoutAdditionalFields = new QuickAddRow(2, $product->getSku(), 43, $each->getCode());
        $quickAddRowWithoutAdditionalFields->setProduct($product);

        $quickAddRowWithAdditionalFields = new QuickAddRow(3, $product->getSku(), 44, $each->getCode());
        $quickAddRowWithAdditionalFields->setProduct($product);
        $quickAddField1 = new QuickAddField('field1', 'value1');
        $quickAddRowWithAdditionalFields->addAdditionalField($quickAddField1);
        $quickAddField2 = new QuickAddField('field2', ['key1' => 'value1']);
        $quickAddRowWithAdditionalFields->addAdditionalField($quickAddField2);

        $quickAddRowWithErrors = new QuickAddRow(4, $product->getSku(), 45, $each->getCode());
        $quickAddRowWithErrors->setProduct($product);
        $errorMessage = 'sample message';
        $errorMessageParameters = ['sample_key' => 'sample_value'];
        $errorPropertyPath = 'samplePath';
        $quickAddRowWithErrors->addError($errorMessage, $errorMessageParameters, $errorPropertyPath);
        $quickAddRowWithErrors->addWarning($errorMessage, $errorMessageParameters, $errorPropertyPath);

        $quickAddRowWithInvalidUnit = new QuickAddRow(5, $product->getSku(), 46, 'invalid_unit');
        $quickAddRowWithInvalidUnit->setProduct($product);

        $emptyQuickAddRowCollectionWithError = new QuickAddRowCollection();
        $emptyQuickAddRowCollectionWithError->addError('sample error');

        return [
            'without product, additional fields, errors' => [
                new QuickAddRowCollection([$quickAddRowWithoutProductAndAdditional]),
                [
                    'errors' => [],
                    'items' => [
                        1 => [
                            'index' => 1,
                            'sku' => $quickAddRowWithoutProductAndAdditional->getSku(),
                            'product_name' => '',
                            'unit_label' => $quickAddRowWithoutProductAndAdditional->getUnit(),
                            'quantity' => $quickAddRowWithoutProductAndAdditional->getQuantity(),
                            'errors' => [],
                            'warnings' => [],
                            'additional' => [],
                            'organization' => null,
                        ],
                    ],
                ],
            ],
            'without additional fields, errors' => [
                new QuickAddRowCollection([$quickAddRowWithoutAdditionalFields]),
                [
                    'errors' => [],
                    'items' => [
                        2 => [
                            'index' => 2,
                            'sku' => $quickAddRowWithoutAdditionalFields->getSku(),
                            'product_name' => $product->getDefaultName()->getString(),
                            'unit_label' => $quickAddRowWithoutAdditionalFields->getUnit() . ' [label]',
                            'units' => [
                                $productUnitPrecision->getProductUnitCode() => $productUnitPrecision->getPrecision(),
                            ],
                            'quantity' => $quickAddRowWithoutAdditionalFields->getQuantity(),
                            'errors' => [],
                            'additional' => [],
                            'warnings' => [],
                            'organization' => null,
                            'type' => Product::TYPE_SIMPLE,
                        ],
                    ],
                ],
            ],
            'with additional fields' => [
                new QuickAddRowCollection([$quickAddRowWithAdditionalFields]),
                [
                    'errors' => [],
                    'items' => [
                        3 => [
                            'index' => 3,
                            'sku' => $quickAddRowWithAdditionalFields->getSku(),
                            'product_name' => $product->getDefaultName()->getString(),
                            'unit_label' => $quickAddRowWithAdditionalFields->getUnit() . ' [label]',
                            'units' => [
                                $productUnitPrecision->getProductUnitCode() => $productUnitPrecision->getPrecision(),
                            ],
                            'quantity' => $quickAddRowWithAdditionalFields->getQuantity(),
                            'errors' => [],
                            'warnings' => [],
                            'additional' => [
                                $quickAddField1->getName() => $quickAddField1->getValue(),
                                $quickAddField2->getName() => $quickAddField2->getValue(),
                            ],
                            'organization' => null,
                            'type' => Product::TYPE_SIMPLE,
                        ],
                    ],
                ],
            ],
            'items with errors and warnings' => [
                new QuickAddRowCollection([$quickAddRowWithErrors]),
                [
                    'errors' => [],
                    'items' => [
                        4 => [
                            'index' => 4,
                            'sku' => $quickAddRowWithErrors->getSku(),
                            'product_name' => $product->getDefaultName()->getString(),
                            'unit_label' => $quickAddRowWithErrors->getUnit() . ' [label]',
                            'units' => [
                                $productUnitPrecision->getProductUnitCode() => $productUnitPrecision->getPrecision(),
                            ],
                            'quantity' => $quickAddRowWithErrors->getQuantity(),
                            'errors' => [
                                [
                                    'message' => $errorMessage . ' [trans]',
                                    'propertyPath' => $errorPropertyPath,
                                ],
                            ],
                            'warnings' => [
                                [
                                    'message' => $errorMessage . ' [trans]',
                                    'propertyPath' => $errorPropertyPath,
                                ],
                            ],
                            'additional' => [],
                            'organization' => null,
                            'type' => Product::TYPE_SIMPLE,
                        ],
                    ],
                ],
            ],
            'items with invalid unit' => [
                new QuickAddRowCollection([$quickAddRowWithInvalidUnit]),
                [
                    'errors' => [],
                    'items' => [
                        5 => [
                            'index' => 5,
                            'sku' => $quickAddRowWithInvalidUnit->getSku(),
                            'product_name' => $product->getDefaultName()->getString(),
                            'unit_label' => $quickAddRowWithInvalidUnit->getUnit(),
                            'units' => [
                                $productUnitPrecision->getProductUnitCode() => $productUnitPrecision->getPrecision(),
                            ],
                            'quantity' => $quickAddRowWithInvalidUnit->getQuantity(),
                            'errors' => [],
                            'warnings' => [],
                            'additional' => [],
                            'organization' => null,
                            'type' => Product::TYPE_SIMPLE,
                        ],
                    ],
                ],
            ],
            'collection with errors' => [
                $emptyQuickAddRowCollectionWithError,
                [
                    'errors' => [['message' => 'sample error [trans]']],
                    'items' => [],
                ],
            ],
        ];
    }
}
