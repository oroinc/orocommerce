<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\DataTransformer\QuickAddRowCollectionTransformer;
use Oro\Bundle\ProductBundle\Form\Type\ProductAutocompleteType;
use Oro\Bundle\ProductBundle\Form\Type\ProductRowCollectionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductRowType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitsType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddRowCollectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Oro\Bundle\ProductBundle\Helper\ProductGrouper\ProductsGrouperFactory;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder;
use Oro\Bundle\ProductBundle\Model\ProductRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductAutocompleteType;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuickAddComponentProcessorValidator;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuickAddTypeTest extends FormIntegrationTestCase
{
    private QuickAddType $formType;

    protected function setUp(): void
    {
        $this->formType = new QuickAddType(new ProductsGrouperFactory());

        parent::setUp();
    }

    protected function getValidators(): array
    {
        $quickAddComponentProcessorValidator = $this->createMock(QuickAddComponentProcessorValidator::class);

        return [
            QuickAddComponentProcessorValidator::class => $quickAddComponentProcessorValidator,
        ];
    }

    /**
     * @return array
     */
    protected function getExtensions(): array
    {
        $unitsProviderMock = $this->createMock(ProductUnitsProvider::class);
        $unitsProviderMock->expects(self::any())
            ->method('getAvailableProductUnits')
            ->willReturn([]);

        $quickAddRowCollectionBuilder = $this->createMock(QuickAddRowCollectionBuilder::class);
        $quickAddRowCollectionBuilder
            ->expects(self::any())
            ->method('buildFromArray')
            ->willReturnCallback(function (array $array) {
                return new QuickAddRowCollection(
                    [
                        new QuickAddRow(
                            1,
                            $array[0][ProductDataStorage::PRODUCT_SKU_KEY],
                            $array[0][ProductDataStorage::PRODUCT_QUANTITY_KEY] ?? 0
                        ),
                    ]
                );
            });

        $quickAddRowCollectionTransformer = new QuickAddRowCollectionTransformer($quickAddRowCollectionBuilder);

        return [
            new PreloadedExtension([
                $this->formType,
                ProductRowCollectionType::class => new ProductRowCollectionType(),
                ProductRowType::class => new ProductRowType($unitsProviderMock),
                CollectionType::class => new CollectionType(),
                ProductAutocompleteType::class => new StubProductAutocompleteType(),
                ProductUnitsType::class => new ProductUnitsType($unitsProviderMock),
                QuickAddRowCollectionType::class => new QuickAddRowCollectionType($quickAddRowCollectionTransformer),
            ], []),
            $this->getValidatorExtension(true),
        ];
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param mixed $submittedData
     * @param mixed $expectedData
     */
    public function testSubmit($submittedData, $expectedData): void
    {
        $products = [new Product(), new Product()];
        $options = [
            'products' => $products,
        ];

        $form = $this->factory->create(QuickAddType::class, null, $options);
        $form->submit($submittedData);

        $collectionProducts = $form->get(QuickAddType::PRODUCTS_FIELD_NAME)->getConfig()->getOption('products');
        self::assertEquals($products, $collectionProducts);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        $productRow = new ProductRow();
        $productRow->productSku = 'sku';
        $productRow->productQuantity = 42;
        return [
            'valid data' => [
                'submittedData' => [
                    QuickAddType::PRODUCTS_FIELD_NAME => [
                        [
                            ProductDataStorage::PRODUCT_SKU_KEY => 'sku',
                            ProductDataStorage::PRODUCT_QUANTITY_KEY => '42',
                        ],
                    ],
                    QuickAddType::COMPONENT_FIELD_NAME => 'component',
                    QuickAddType::ADDITIONAL_FIELD_NAME => 'additional',
                    QuickAddType::TRANSITION_FIELD_NAME => 'start_from_quickorderform',
                ],
                'expectedData' => [
                    QuickAddType::PRODUCTS_FIELD_NAME => [
                        $productRow,
                    ],
                    QuickAddType::COMPONENT_FIELD_NAME => 'component',
                    QuickAddType::ADDITIONAL_FIELD_NAME => 'additional',
                    QuickAddType::TRANSITION_FIELD_NAME => 'start_from_quickorderform',
                ],
            ],
        ];
    }

    public function testSubmitWhenIsOptimized(): void
    {
        $form = $this->factory->create(QuickAddType::class, null, ['is_optimized' => true]);
        $form->submit([
            QuickAddType::PRODUCTS_FIELD_NAME => json_encode([
                [
                    ProductDataStorage::PRODUCT_SKU_KEY => 'sku42',
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => '42',
                ],
            ]),
            QuickAddType::COMPONENT_FIELD_NAME => 'component',
            QuickAddType::ADDITIONAL_FIELD_NAME => 'additional',
            QuickAddType::TRANSITION_FIELD_NAME => 'start_from_quickorderform',
        ]);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals(
            [
                QuickAddType::COMPONENT_FIELD_NAME => 'component',
                QuickAddType::ADDITIONAL_FIELD_NAME => 'additional',
                QuickAddType::TRANSITION_FIELD_NAME => 'start_from_quickorderform',
            ],
            $form->getData()
        );
        $quickAddRowCollection = $form->get(QuickAddType::PRODUCTS_FIELD_NAME)->getData();
        self::assertInstanceOf(QuickAddRowCollection::class, $quickAddRowCollection);
        self::assertEquals(new QuickAddRowCollection([new QuickAddRow(1, 'sku42', 42)]), $quickAddRowCollection);
    }

    public function testInvalidSubmit(): void
    {
        $form = $this->factory->create(QuickAddType::class);
        $form->submit([]);
        self::assertFalse($form->isValid());
        self::assertTrue($form->isSynchronized());
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with(
                self::callback(
                    function (array $options) {
                        $this->assertArrayHasKey('products', $options);
                        $this->assertNull($options['products']);
                        $this->assertArrayHasKey('is_optimized', $options);
                        $this->assertFalse($options['is_optimized']);
                        return true;
                    }
                )
            );

        $this->formType->configureOptions($resolver);
    }

    /**
     * @dataProvider isOptimizedDataProvider
     */
    public function testFinishView(bool $isOptimized): void
    {
        $form = $this->factory->create(QuickAddImportFromFileType::class, null, ['is_optimized' => $isOptimized]);
        $formView = $form->createView();

        self::assertSame($isOptimized, $formView->vars['is_optimized']);
    }

    public function isOptimizedDataProvider(): array
    {
        return [[true], [false]];
    }
}
