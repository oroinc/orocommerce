<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\QuickAdd\Normalizer;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Model\QuickAddField;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\QuickAdd\Normalizer\BasicQuickAddCollectionNormalizer;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Symfony\Contracts\Translation\TranslatorInterface;

class BasicQuickAddCollectionNormalizerTest extends \PHPUnit\Framework\TestCase
{
    private BasicQuickAddCollectionNormalizer $normalizer;

    protected function setUp(): void
    {
        $localizationHelper = $this->createMock(LocalizationHelper::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $unitLabelFormatter = $this->createMock(UnitLabelFormatterInterface::class);
        $unitLabelFormatter
            ->expects(self::any())
            ->method('format')
            ->willReturnCallback(static fn (string $code) => $code . ' [label]');

        $this->normalizer = new BasicQuickAddCollectionNormalizer(
            $localizationHelper,
            $unitLabelFormatter,
            $translator
        );

        $translator
            ->expects(self::any())
            ->method('trans')
            ->willReturnCallback(static function (string $key, array $parameters, string $domain) {
                self::assertEquals('validators', $domain);

                return $key . ' [trans]';
            });

        $localizationHelper
            ->expects(self::any())
            ->method('getLocalizedValue')
            ->willReturnCallback(static function (Collection $collection) {
                return $collection[0]->getString();
            });
    }

    public function testGetResultsWhenEmpty(): void
    {
        $quickAddRowCollection = new QuickAddRowCollection();

        self::assertEquals(['errors' => [], 'items' => []], $this->normalizer->normalize($quickAddRowCollection));
    }

    /**
     * @dataProvider getResultsDataProvider
     *
     * @param QuickAddRowCollection $quickAddRowCollection
     * @param array $expected
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

        $each = (new ProductUnit())
            ->setCode('each');
        $productUnitPrecision = (new ProductUnitPrecision())
            ->setUnit($each)
            ->setPrecision(1);
        $product = (new ProductStub())
            ->setSku('SKU2')
            ->addName((new ProductName())->setString('Sample Name'))
            ->addUnitPrecision($productUnitPrecision);
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

        $quickAddRowWithInvalidUnit = new QuickAddRow(5, $product->getSku(), 46, 'invalid_unit');
        $quickAddRowWithInvalidUnit->setProduct($product);

        return [
            'without product, additional fields, errors' => [
                new QuickAddRowCollection([$quickAddRowWithoutProductAndAdditional]),
                [
                    'errors' => [],
                    'items' => [
                        0 => [
                            'sku' => $quickAddRowWithoutProductAndAdditional->getSku(),
                            'product_name' => '',
                            'unit_label' => $quickAddRowWithoutProductAndAdditional->getUnit(),
                            'quantity' => $quickAddRowWithoutProductAndAdditional->getQuantity(),
                            'errors' => [],
                            'additional' => [],
                        ],
                    ],
                ],
            ],
            'without additional fields, errors' => [
                new QuickAddRowCollection([$quickAddRowWithoutAdditionalFields]),
                [
                    'errors' => [],
                    'items' => [
                        0 => [
                            'sku' => $quickAddRowWithoutAdditionalFields->getSku(),
                            'product_name' => $product->getDefaultName()->getString(),
                            'unit_label' => $quickAddRowWithoutAdditionalFields->getUnit() . ' [label]',
                            'units' => [
                                $productUnitPrecision->getProductUnitCode() => $productUnitPrecision->getPrecision(),
                            ],
                            'quantity' => $quickAddRowWithoutAdditionalFields->getQuantity(),
                            'errors' => [],
                            'additional' => [],
                        ],
                    ],
                ],
            ],
            'with additional fields' => [
                new QuickAddRowCollection([$quickAddRowWithAdditionalFields]),
                [
                    'errors' => [],
                    'items' => [
                        0 => [
                            'sku' => $quickAddRowWithAdditionalFields->getSku(),
                            'product_name' => $product->getDefaultName()->getString(),
                            'unit_label' => $quickAddRowWithAdditionalFields->getUnit() . ' [label]',
                            'units' => [
                                $productUnitPrecision->getProductUnitCode() => $productUnitPrecision->getPrecision(),
                            ],
                            'quantity' => $quickAddRowWithAdditionalFields->getQuantity(),
                            'errors' => [],
                            'additional' => [
                                $quickAddField1->getName() => $quickAddField1->getValue(),
                                $quickAddField2->getName() => $quickAddField2->getValue(),
                            ],
                        ],
                    ],
                ],
            ],
            'items with errors' => [
                new QuickAddRowCollection([$quickAddRowWithErrors]),
                [
                    'errors' => [],
                    'items' => [
                        0 => [
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
                            'additional' => [],
                        ],
                    ],
                ],
            ],
            'items with invalid unit' => [
                new QuickAddRowCollection([$quickAddRowWithInvalidUnit]),
                [
                    'errors' => [],
                    'items' => [
                        0 => [
                            'sku' => $quickAddRowWithInvalidUnit->getSku(),
                            'product_name' => $product->getDefaultName()->getString(),
                            'unit_label' => $quickAddRowWithInvalidUnit->getUnit(),
                            'units' => [
                                $productUnitPrecision->getProductUnitCode() => $productUnitPrecision->getPrecision(),
                            ],
                            'quantity' => $quickAddRowWithInvalidUnit->getQuantity(),
                            'errors' => [],
                            'additional' => [],
                        ],
                    ],
                ],
            ],
            'collection with errors' => [
                (new QuickAddRowCollection())->addError('sample error'),
                [
                    'errors' => [['message' => 'sample error [trans]']],
                    'items' => [],
                ],
            ],
        ];
    }
}
