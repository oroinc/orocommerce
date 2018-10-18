<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Search;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Search\ProductIndexDataModel;
use Oro\Bundle\ProductBundle\Search\ProductIndexDataProviderInterface;
use Oro\Bundle\ProductBundle\Search\ProductVariantIndexDataProviderDecorator;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductVariantIndexDataProviderDecoratorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ProductIndexDataProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $originalProvider;

    /** @var ProductVariantIndexDataProviderDecorator */
    private $productVariantProvider;

    protected function setUp()
    {
        $this->originalProvider = $this->createMock(ProductIndexDataProviderInterface::class);

        $this->productVariantProvider = new ProductVariantIndexDataProviderDecorator($this->originalProvider);
    }

    /**
     * @param FieldConfigModel $attribute
     * @param array $firstVariantData
     * @param array $secondVariantDate
     * @param array $configurableData
     * @param array $expectedConfigurableData
     *
     * @dataProvider getIndexDataDataProvider
     */
    public function testGetIndexData(
        FieldConfigModel $attribute,
        array $firstVariantData,
        array $secondVariantDate,
        array $configurableData,
        array $expectedConfigurableData
    ) {
        $firstSimpleProduct = $this->getEntity(Product::class, ['id' => 1, 'type' => Product::TYPE_SIMPLE]);
        /** @var ProductVariantLink $firstVariantLink */
        $firstVariantLink = $this->getEntity(ProductVariantLink::class, ['product' => $firstSimpleProduct]);

        $secondSimpleProduct = $this->getEntity(Product::class, ['id' => 2, 'type' => Product::TYPE_SIMPLE]);
        /** @var ProductVariantLink $secondVariantLink */
        $secondVariantLink = $this->getEntity(ProductVariantLink::class, ['product' => $secondSimpleProduct]);

        /** @var Product $configurableProduct */
        $configurableProduct = $this->getEntity(Product::class, ['id' => 3, 'type' => Product::TYPE_CONFIGURABLE]);
        $configurableProduct
            ->addVariantLink($firstVariantLink)
            ->addVariantLink($secondVariantLink);

        $this->originalProvider->expects($this->any())
            ->method('getIndexData')
            ->willReturnMap([
                [$firstSimpleProduct, $attribute, [], $firstVariantData],
                [$secondSimpleProduct, $attribute, [], $secondVariantDate],
                [$configurableProduct, $attribute, [], $configurableData],
            ]);

        $this->assertEquals(
            $expectedConfigurableData,
            $this->productVariantProvider->getIndexData($configurableProduct, $attribute, [])
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function getIndexDataDataProvider()
    {
        return [
            'all text not localized' => [
                'attribute' => $this->getEntity(
                    FieldConfigModel::class,
                    ['fieldName' => 'sku', 'type' => 'string']
                ),
                'first simple product data' => [
                    new ProductIndexDataModel('sku', 'FIRST', [], false, true),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_FIELD, 'FIRST', [], false, true),
                ],
                'second simple product data' => [
                    new ProductIndexDataModel('sku', 'SECOND', [], false, true),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_FIELD, 'SECOND', [], false, true),
                ],
                'configurable product data' => [
                    new ProductIndexDataModel('sku', 'CONFIG', [], false, true),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_FIELD, 'CONFIG', [], false, true),
                ],
                'expected configurable product data' => [
                    new ProductIndexDataModel('sku', 'CONFIG', [], false, true),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_FIELD, 'CONFIG', [], false, true),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_FIELD, 'FIRST', [], false, true),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_FIELD, 'SECOND', [], false, true),
                ],
            ],
            'all text localized' => [
                'attribute' => $this->getEntity(
                    FieldConfigModel::class,
                    ['fieldName' => 'name', 'type' => 'string']
                ),
                'first simple product data' => [
                    new ProductIndexDataModel('name', 'First product', [], true, true),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'First product', [], true, true),
                ],
                'second simple product data' => [
                    new ProductIndexDataModel('name', 'Second product', [], true, true),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'Second product', [], true, true),
                ],
                'configurable product data' => [
                    new ProductIndexDataModel('name', 'Config product', [], true, true),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'Config product', [], true, true),
                ],
                'expected configurable product data' => [
                    new ProductIndexDataModel('name', 'Config product', [], true, true),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'Config product', [], true, true),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'First product', [], true, true),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'Second product', [], true, true),
                ],
            ],
            'enum' => [
                'attribute' => $this->getEntity(
                    FieldConfigModel::class,
                    ['fieldName' => 'color', 'type' => 'enum']
                ),
                'first simple product data' => [
                    new ProductIndexDataModel('color_red', 1, [], false, false),
                    new ProductIndexDataModel('color_priority', 10, [], false, false),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'Red', [], true, true),
                ],
                'second simple product data' => [
                    new ProductIndexDataModel('color_green', 1, [], false, false),
                    new ProductIndexDataModel('color_priority', 20, [], false, false),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'Green', [], true, true),
                ],
                'configurable product data' => [
                    new ProductIndexDataModel('color_blue', 1, [], false, false),
                    new ProductIndexDataModel('color_priority', 30, [], false, false),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'Blue', [], true, true),
                ],
                'expected configurable product data' => [
                    new ProductIndexDataModel('color_blue', 1, [], false, false),
                    new ProductIndexDataModel('color_priority', 30, [], false, false),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'Blue', [], true, true),
                    new ProductIndexDataModel('color_red', 1, [], false, false),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'Red', [], true, true),
                    new ProductIndexDataModel('color_green', 1, [], false, false),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'Green', [], true, true),
                ],
            ],
            'multi-enum' => [
                'attribute' => $this->getEntity(
                    FieldConfigModel::class,
                    ['fieldName' => 'options', 'type' => 'multiEnum']
                ),
                'first simple product data' => [
                    new ProductIndexDataModel('options_pocket', 1, [], false, false),
                    new ProductIndexDataModel('options_collar', 1, [], false, false),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'Pocket Collar', [], true, true),
                ],
                'second simple product data' => [
                    new ProductIndexDataModel('options_seams', 1, [], false, false),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'Seams', [], true, true),
                ],
                'configurable product data' => [
                    new ProductIndexDataModel('options_pocket', 1, [], false, false),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'Pocket', [], true, true),
                ],
                'expected configurable product data' => [
                    new ProductIndexDataModel('options_pocket', 1, [], false, false),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'Pocket', [], true, true),
                    new ProductIndexDataModel('options_collar', 1, [], false, false),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'Pocket Collar', [], true, true),
                    new ProductIndexDataModel('options_seams', 1, [], false, false),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'Seams', [], true, true),
                ],
            ],
        ];
    }
}
