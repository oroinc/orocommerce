<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\ProductBundle\Form\DataTransformer\QuickAddRowCollectionTransformer;
use Oro\Bundle\ProductBundle\Form\Type\ProductAutocompleteType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitsType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddRowCollectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddRowType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductAutocompleteType;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuickAddComponentProcessorValidator;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class QuickAddTypeTest extends FormIntegrationTestCase
{
    private QuickAddType $formType;

    private ProductUnitsProvider|\PHPUnit\Framework\MockObject\MockObject $unitsProvider;

    private QuickAddRowCollectionTransformer $quickAddRowCollectionTransformer;

    protected function setUp(): void
    {
        $this->formType = new QuickAddType();

        $this->unitsProvider = $this->createMock(ProductUnitsProvider::class);
        $this->unitsProvider
            ->expects(self::any())
            ->method('getAvailableProductUnits')
            ->willReturn(['Item' => 'item']);

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

        $this->quickAddRowCollectionTransformer = new QuickAddRowCollectionTransformer($quickAddRowCollectionBuilder);

        parent::setUp();
    }

    protected function getValidators(): array
    {
        $quickAddComponentProcessorValidator = $this->createMock(QuickAddComponentProcessorValidator::class);

        return [
            QuickAddComponentProcessorValidator::class => $quickAddComponentProcessorValidator,
        ];
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([
                $this->formType,
                CollectionType::class => new CollectionType(),
                ProductAutocompleteType::class => new StubProductAutocompleteType(),
                ProductUnitsType::class => new ProductUnitsType($this->unitsProvider),
                QuickAddRowCollectionType::class =>
                    new QuickAddRowCollectionType($this->quickAddRowCollectionTransformer),
                QuickAddRowType::class => new QuickAddRowType($this->unitsProvider),
            ], []),
            $this->getValidatorExtension(true),
        ];
    }

    public function testSubmit(): void
    {
        $form = $this->factory->create(QuickAddType::class);
        $form->submit([
            QuickAddType::PRODUCTS_FIELD_NAME => json_encode([
                [
                    QuickAddRow::SKU => 'sku42',
                    QuickAddRow::QUANTITY => '42',
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
}
