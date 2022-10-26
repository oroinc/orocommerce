<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Form\Type\ProductAutocompleteType;
use Oro\Bundle\ProductBundle\Form\Type\ProductRowType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitsType;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductAutocompleteType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class ProductRowTypeTest extends FormIntegrationTestCase
{
    private ProductUnitsProvider|\PHPUnit\Framework\MockObject\MockObject $productUnitsProvider;

    protected function setUp(): void
    {
        $this->productUnitsProvider = $this->createMock(ProductUnitsProvider::class);
        $this->productUnitsProvider
            ->expects(self::any())
            ->method('getAvailableProductUnits')
            ->willReturn(['Item' => 'item']);

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    ProductAutocompleteType::class => new StubProductAutocompleteType(),
                    ProductUnitsType::class => new ProductUnitsType($this->productUnitsProvider),
                    ProductRowType::class => new ProductRowType($this->productUnitsProvider),
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        ?array $defaultData,
        array $submittedData,
        array $expectedData
    ): void {
        $form = $this->factory->create(ProductRowType::class, $defaultData);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $data = $form->getData();

        $this->assertEquals($expectedData, $data);
    }

    public function submitDataProvider(): array
    {
        return [
            'without default data' => [
                'defaultData' => null,
                'submittedData' => [
                    ProductDataStorage::PRODUCT_SKU_KEY => 'SKU_001',
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => '10',
                ],
                'expectedData' => [
                    ProductDataStorage::PRODUCT_SKU_KEY => 'SKU_001',
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => 10.0,
                    ProductDataStorage::PRODUCT_UNIT_KEY => '',
                ],
            ],
            'with default data' => [
                'defaultData' => [
                    ProductDataStorage::PRODUCT_SKU_KEY => 'SKU_001',
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => '10',
                ],
                'submittedData' => [
                    ProductDataStorage::PRODUCT_SKU_KEY => 'SKU_002',
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => '20',
                    ProductDataStorage::PRODUCT_UNIT_KEY => 'item',
                ],
                'expectedData' => [
                    ProductDataStorage::PRODUCT_SKU_KEY => 'SKU_002',
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => 20.0,
                    ProductDataStorage::PRODUCT_UNIT_KEY => 'item',
                ],
            ],
        ];
    }
}
