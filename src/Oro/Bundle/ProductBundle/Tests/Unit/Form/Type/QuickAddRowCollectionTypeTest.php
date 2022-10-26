<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Form\DataTransformer\QuickAddRowCollectionTransformer;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddRowCollectionType;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuickAddRowCollectionTypeTest extends FormIntegrationTestCase
{
    private QuickAddRowCollectionType $formType;

    protected function setUp(): void
    {
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

        $this->formType = new QuickAddRowCollectionType($quickAddRowCollectionTransformer);

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([
                $this->formType,
            ], []),
            $this->getValidatorExtension(false),
        ];
    }

    public function testSubmit(): void
    {
        $form = $this->factory->create(QuickAddRowCollectionType::class);
        $form->submit(
            json_encode([
                [
                    ProductDataStorage::PRODUCT_SKU_KEY => 'sku42',
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => '42',
                ],
            ])
        );

        $quickAddRowCollection = $form->getData();
        self::assertInstanceOf(QuickAddRowCollection::class, $quickAddRowCollection);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals(new QuickAddRowCollection([new QuickAddRow(1, 'sku42', 42)]), $quickAddRowCollection);
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
                'data_class' => QuickAddRowCollection::class,
                'error_bubbling' => false,
            ],
            $resolver->resolve([])
        );
    }
}
