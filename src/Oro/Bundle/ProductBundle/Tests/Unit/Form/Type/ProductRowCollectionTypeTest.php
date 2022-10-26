<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductAutocompleteType;
use Oro\Bundle\ProductBundle\Form\Type\ProductRowCollectionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductRowType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitsType;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductAutocompleteType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

class ProductRowCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @return array
     */
    protected function getExtensions()
    {
        $unitsProviderMock = $this->createMock(ProductUnitsProvider::class);
        $unitsProviderMock->expects($this->any())
            ->method('getAvailableProductUnits')
            ->willReturn([]);

        return [
            new PreloadedExtension(
                [
                    CollectionType::class => new CollectionType(),
                    ProductRowType::class => new ProductRowType($unitsProviderMock),
                    ProductAutocompleteType::class => new StubProductAutocompleteType(),
                    ProductUnitsType::class => new ProductUnitsType($unitsProviderMock),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    /**
     * @param array|null $defaultData
     * @param array|null $submittedData
     * @param array|null $expectedData
     * @param array $options
     * @dataProvider submitDataProvider
     */
    public function testSubmit($defaultData, $submittedData, $expectedData, array $options)
    {
        $form = $this->factory->create(ProductRowCollectionType::class, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider()
    {
        return [
            'without submitted data' => [
                'defaultData' => null,
                'submittedData' => null,
                'expectedData' => [],
                'options' => [],
            ],
            'without default data' => [
                'defaultData' => null,
                'submittedData' => [
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => 'SKU_001',
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => '',
                    ],
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => 'SKU_002',
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => '20',
                    ],
                ],
                'expectedData' => [
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => 'SKU_001',
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => null,
                        ProductDataStorage::PRODUCT_UNIT_KEY => '',
                    ],
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => 'SKU_002',
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => 20.0,
                        ProductDataStorage::PRODUCT_UNIT_KEY => '',
                    ],
                ],
                'options' => [],
            ],
            'with default data' => [
                'defaultData' => [
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => 'SKU',
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => 42,
                    ],
                ],
                'submittedData' => [
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => 'SKU_003',
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => 30.0,
                    ],
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => 'SKU_004',
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => 40.0,
                    ],
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => 'SKU_005',
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => 50.0,
                    ],
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => '',
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => 0,
                    ],
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => '',
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => 0,
                    ],
                ],
                'expectedData' => [
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => 'SKU_003',
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => 30.0,
                        ProductDataStorage::PRODUCT_UNIT_KEY => '',
                    ],
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => 'SKU_004',
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => 40.0,
                        ProductDataStorage::PRODUCT_UNIT_KEY => '',
                    ],
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => 'SKU_005',
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => 50.0,
                        ProductDataStorage::PRODUCT_UNIT_KEY => '',
                    ],
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => '',
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => 0,
                        ProductDataStorage::PRODUCT_UNIT_KEY => '',
                    ],
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => '',
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => 0,
                        ProductDataStorage::PRODUCT_UNIT_KEY => '',
                    ],
                ],
                'options' => [],
            ],
        ];
    }
}
