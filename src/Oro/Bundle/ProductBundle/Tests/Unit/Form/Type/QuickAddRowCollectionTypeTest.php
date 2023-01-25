<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\ProductBundle\Form\DataTransformer\QuickAddRowCollectionTransformer;
use Oro\Bundle\ProductBundle\Form\Type\ProductAutocompleteType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitsType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddRowCollectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddRowType;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductAutocompleteType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuickAddRowCollectionTypeTest extends FormIntegrationTestCase
{
    private QuickAddRowCollectionType $formType;

    private ProductUnitsProvider|\PHPUnit\Framework\MockObject\MockObject $unitsProvider;

    protected function setUp(): void
    {
        $quickAddRowCollectionBuilder = $this->createMock(QuickAddRowCollectionBuilder::class);
        $quickAddRowCollectionBuilder
            ->expects(self::any())
            ->method('buildFromArray')
            ->willReturnCallback(static function (array $array) {
                return new QuickAddRowCollection(
                    $array ? [
                        new QuickAddRow(
                            1,
                            $array[0][QuickAddRow::SKU],
                            $array[0][QuickAddRow::QUANTITY] ?? 0,
                            $array[0][QuickAddRow::UNIT] ?? null,
                        ),
                    ] : []
                );
            });

        $quickAddRowCollectionTransformer = new QuickAddRowCollectionTransformer($quickAddRowCollectionBuilder);

        $this->unitsProvider = $this->createMock(ProductUnitsProvider::class);
        $this->unitsProvider
            ->expects(self::any())
            ->method('getAvailableProductUnits')
            ->willReturn(['Item' => 'item']);

        $this->formType = new QuickAddRowCollectionType($quickAddRowCollectionTransformer);

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([
                $this->formType,
                CollectionType::class => new CollectionType(),
                ProductAutocompleteType::class => new StubProductAutocompleteType(),
                ProductUnitsType::class => new ProductUnitsType($this->unitsProvider),
                QuickAddRowType::class => new QuickAddRowType($this->unitsProvider),
            ], []),
            $this->getValidatorExtension(false),
        ];
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $data, array $expected): void
    {
        $form = $this->factory->create(QuickAddRowCollectionType::class);
        $form->submit(json_encode($data));

        $quickAddRowCollection = $form->getData();
        self::assertInstanceOf(QuickAddRowCollection::class, $quickAddRowCollection);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals(new QuickAddRowCollection($expected), $quickAddRowCollection);
    }

    public function submitDataProvider(): array
    {
        return [
            'empty' => ['data' => [], 'expected' => []],
            'without unit' => [
                'data' => [
                    [
                        QuickAddRow::SKU => 'sku42',
                        QuickAddRow::QUANTITY => '42',
                    ],
                ],
                'expected' => [new QuickAddRow(1, 'sku42', 42)],
            ],
            'with unit' => [
                'data' => [
                    [
                        QuickAddRow::SKU => 'sku142',
                        QuickAddRow::QUANTITY => '142',
                        QuickAddRow::UNIT => 'item',
                    ],
                ],
                'expected' => [new QuickAddRow(1, 'sku142', 142, 'item')],
            ],
        ];
    }

    public function testSubmitWhenInvalidJson(): void
    {
        $form = $this->factory->create(QuickAddRowCollectionType::class);
        $form->submit('invalid');

        $quickAddRowCollection = $form->getData();
        self::assertNull($quickAddRowCollection);

        self::assertFalse($form->isValid());
        self::assertFalse($form->isSynchronized());
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        self::assertEquals(
            [
                'compound' => false,
                'data_class' => QuickAddRowCollection::class,
                'error_bubbling' => false,
                'prototype_name' => '__row__',
                'entry_type' => QuickAddRowType::class,
                'handle_primary' => false,
                'row_count_add' => 5,
                'row_count_initial' => 8,
            ],
            $resolver->resolve([])
        );
    }
}
