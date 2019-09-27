<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductAutocompleteType;
use Oro\Bundle\ProductBundle\Form\Type\ProductRowCollectionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductRowType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitsType;
use Oro\Bundle\ProductBundle\Model\ProductRow;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductAutocompleteType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
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
                    ProductUnitsType::class => new ProductUnitsType($unitsProviderMock)
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
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
                'options' => []
            ],
            'without default data' => [
                'defaultData' => null,
                'submittedData' => [
                    [
                        'productSku' => 'SKU_001',
                        'productQuantity' => ''
                    ],
                    [
                        'productSku' => 'SKU_002',
                        'productQuantity' => '20'
                    ]
                ],
                'expectedData' => [
                    $this->createProductRow('SKU_001', '1'),
                    $this->createProductRow('SKU_002', '20')
                ],
                'options' => []
            ],
            'with default data' => [
                'defaultData' => [
                    $this->createProductRow('SKU', '42')
                ],
                'submittedData' => [
                    [
                        'productSku' => 'SKU_003',
                        'productQuantity' => '30'
                    ],
                    [
                        'productSku' => 'SKU_004',
                        'productQuantity' => '40'
                    ],
                    [
                        'productSku' => 'SKU_005',
                        'productQuantity' => '50'
                    ],
                    [
                        'productSku' => '',
                        'productQuantity' => ''
                    ],
                    [
                        'productSku' => '',
                        'productQuantity' => ''
                    ],
                ],
                'expectedData' => [
                    $this->createProductRow('SKU_003', '30'),
                    $this->createProductRow('SKU_004', '40'),
                    $this->createProductRow('SKU_005', '50')

                ],
                'options' => []
            ]
        ];
    }

    public function testConfigureOptions()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|OptionsResolver $resolver */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                $this->callback(
                    function (array $options) {
                        $this->assertArrayHasKey('products', $options);
                        $this->assertNull($options['products']);
                        return true;
                    }
                )
            );

        $formType = new ProductRowCollectionType();
        $formType->configureOptions($resolver);
    }

    /**
     * @param string $sku
     * @param string $qty
     * @return ProductRow
     */
    protected function createProductRow($sku, $qty)
    {
        $productRow = new ProductRow();
        $productRow->productSku = $sku;
        $productRow->productQuantity= $qty;

        return $productRow;
    }
}
