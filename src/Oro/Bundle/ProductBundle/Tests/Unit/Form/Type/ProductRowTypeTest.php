<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductAutocompleteType;
use Oro\Bundle\ProductBundle\Form\Type\ProductRowType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitsType;
use Oro\Bundle\ProductBundle\Model\ProductRow;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductAutocompleteType;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductBySkuValidator;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\ConstraintValidator;

class ProductRowTypeTest extends FormIntegrationTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConstraintValidator */
    private $validator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ProductUnitsProvider */
    private $productUnitsProvider;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ProductBySkuValidator::class);

        $this->productUnitsProvider = $this->createMock(ProductUnitsProvider::class);
        $this->productUnitsProvider->expects($this->any())
            ->method('getAvailableProductUnits')
            ->willReturn([]);

        parent::setUp();
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        ?ProductRow $defaultData,
        array $submittedData,
        ProductRow $expectedData,
        array $options = []
    ) {
        if (count($options)) {
            $this->validator->expects($this->once())
                ->method('validate')
                ->willReturn(true);
        }

        $form = $this->factory->create(ProductRowType::class, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $data = $form->getData();

        $this->assertEquals($expectedData, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        $unitsProviderMock = $this->createMock(ProductUnitsProvider::class);
        $unitsProviderMock->expects($this->any())
            ->method('getAvailableProductUnits')
            ->willReturn([]);

        return [
            new PreloadedExtension(
                [
                    ProductAutocompleteType::class => new StubProductAutocompleteType(),
                    ProductUnitsType::class => new ProductUnitsType($unitsProviderMock),
                    ProductRowType::class => new ProductRowType($this->productUnitsProvider)
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getValidators(): array
    {
        return [
            'oro_product_product_by_sku_validator' => $this->validator
        ];
    }

    public function submitDataProvider(): array
    {
        return [
            'without default data' => [
                'defaultData' => null,
                'submittedData' => [
                    ProductDataStorage::PRODUCT_SKU_KEY => 'SKU_001',
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => '10'
                ],
                'expectedData' => $this->createProductRow('SKU_001', '10')
            ],
            'with default data' => [
                'defaultData' => $this->createProductRow('SKU_001', '10'),
                'submittedData' => [
                    ProductDataStorage::PRODUCT_SKU_KEY => 'SKU_002',
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => '20'
                ],
                'expectedData' =>$this->createProductRow('SKU_002', '20')
            ],
            'with default data and validation' => [
                'defaultData' => $this->createProductRow('SKU_001', '10'),
                'submittedData' => [
                    ProductDataStorage::PRODUCT_SKU_KEY => 'SKU_002',
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => '20'
                ],
                'expectedData' => $this->createProductRow('SKU_002', '20'),
                'options' => [
                    'validation_required' => true
                ]
            ]
        ];
    }

    public function testBuildView()
    {
        $product = new Product();
        $product->setSku('sku123Абв');

        $view = new FormView();

        $config = $this->createMock(FormConfigInterface::class);
        $config->expects($this->any())
            ->method('getOptions')
            ->willReturn(
                [
                    'product' => $product,
                    'product_field' => 'product',
                    'product_holder' => null,
                ]
            );

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);

        $formType = new ProductRowType($this->productUnitsProvider);
        $formType->buildView($view, $form, []);

        $this->assertEquals($product, $view->vars['product']);
    }

    public function testGetProductFromParent()
    {
        $product = new Product();
        $product->setSku('sku1Абв');

        $view = new FormView();

        $config = $this->createMock(FormConfigInterface::class);
        $config->expects($this->any())
            ->method('getOptions')
            ->willReturn(
                [
                    'product' => null,
                    'product_field' => 'product',
                    'product_holder' => null,
                ]
            );
        $config->expects($this->once())
            ->method('getOption')
            ->with('products')
            ->willReturn(
                [
                    'SKU1АБВ' => $product,
                ]
            );

        $parentForm = $this->createMock(FormInterface::class);
        $parentForm->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);

        $skuField = $this->createMock(FormInterface::class);
        $skuField->expects($this->once())
            ->method('getData')
            ->willReturn('sku1Абв');

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);
        $form->expects($this->any())
            ->method('getParent')
            ->willReturn($parentForm);
        $form->expects($this->once())
            ->method('get')
            ->with(ProductDataStorage::PRODUCT_SKU_KEY)
            ->willReturn($skuField);

        $formType = new ProductRowType($this->productUnitsProvider);
        $formType->buildView($view, $form, []);

        $this->assertEquals($product, $view->vars['product']);
    }

    private function createProductRow(string $sku, string $qty): ProductRow
    {
        $productRow = new ProductRow();
        $productRow->productSku = $sku;
        $productRow->productQuantity= $qty;

        return $productRow;
    }
}
